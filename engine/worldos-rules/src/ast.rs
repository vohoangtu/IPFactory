//! AST for WorldOS DSL rules (một mô hình duy nhất).

use serde::{Deserialize, Serialize};
use std::collections::{HashMap, HashSet};

// ---------------------------------------------------------------------------
// Expression
// ---------------------------------------------------------------------------

#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum Expr {
    ConstFloat(f64),
    ConstInt(i64),
    ConstStr(String),
    Path(String),
    Add(Box<Expr>, Box<Expr>),
    Sub(Box<Expr>, Box<Expr>),
    Mul(Box<Expr>, Box<Expr>),
    Div(Box<Expr>, Box<Expr>),
    FunctionCall {
        name: String,
        args: Vec<Expr>,
    },
}

/// Thu thập mọi path được đọc trong biểu thức (phục vụ condition index).
pub fn expr_paths(expr: &Expr) -> HashSet<String> {
    let mut out = HashSet::new();
    fn collect(expr: &Expr, out: &mut HashSet<String>) {
        match expr {
            Expr::Path(p) => {
                out.insert(p.clone());
            }
            Expr::Add(a, b) | Expr::Sub(a, b) | Expr::Mul(a, b) | Expr::Div(a, b) => {
                collect(a, out);
                collect(b, out);
            }
            Expr::FunctionCall { args, .. } => {
                for arg in args {
                    collect(arg, out);
                }
            }
            Expr::ConstFloat(_) | Expr::ConstInt(_) | Expr::ConstStr(_) => {}
        }
    }
    collect(expr, &mut out);
    out
}

// ---------------------------------------------------------------------------
// Condition (dùng nội bộ khi parse từng dòng "path op value")
// ---------------------------------------------------------------------------

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Condition {
    pub path: String,
    pub op: Op,
    pub value: ConditionValue,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum ConditionValue {
    Float(f64),
    Int(i64),
    Str(String),
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Serialize, Deserialize)]
pub enum Op {
    Lt,
    Le,
    Gt,
    Ge,
    Eq,
    Ne,
}

/// Cây điều kiện: comparison hoặc AND/OR/NOT.
#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum ConditionExpr {
    Comparison {
        left: Expr,
        op: Op,
        right: Expr,
    },
    And(Box<ConditionExpr>, Box<ConditionExpr>),
    Or(Box<ConditionExpr>, Box<ConditionExpr>),
    Not(Box<ConditionExpr>),
}

/// Thu thập mọi path được đọc trong điều kiện (phục vụ condition index).
pub fn condition_expr_paths(cond: &ConditionExpr) -> HashSet<String> {
    let mut out = HashSet::new();
    fn collect(cond: &ConditionExpr, out: &mut HashSet<String>) {
        match cond {
            ConditionExpr::Comparison { left, right, .. } => {
                out.extend(expr_paths(left));
                out.extend(expr_paths(right));
            }
            ConditionExpr::And(a, b) | ConditionExpr::Or(a, b) => {
                collect(a, out);
                collect(b, out);
            }
            ConditionExpr::Not(x) => collect(x, out),
        }
    }
    collect(cond, &mut out);
    out
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Serialize, Deserialize)]
pub enum PressureOp {
    Add,
    Set,
    Sub,
}

// ---------------------------------------------------------------------------
// Action
// ---------------------------------------------------------------------------

#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum Action {
    EmitEvent(String),
    AdjustStability(f64),
    AdjustEntropy(f64),
    Add { path: String, value: Expr },
    Set { path: String, value: Expr },
    Decay { path: String, factor: Expr },
    Pressure { name: String, op: PressureOp, value: Expr },
    SpawnActor { kind: String },
    Drift { path: String, target: Option<Expr>, speed: Option<Expr> },
    Calc { name: String, formula: Expr },
    Metadata { key: String, value: String },
}

// ---------------------------------------------------------------------------
// Rule (một mô hình duy nhất)
// ---------------------------------------------------------------------------

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Rule {
    pub name: String,
    #[serde(default)]
    pub priority: u32,
    pub cooldown_ticks: Option<u64>,
    pub scope: Option<String>,
    pub when: ConditionExpr,
    pub chance: Expr,
    pub actions: Vec<Action>,
}

// ---------------------------------------------------------------------------
// Rule Graph (v2.5 — dependencies cho AI / self-improving)
// ---------------------------------------------------------------------------

/// Đồ thị rule: danh sách rule + cạnh phụ thuộc (trigger → effect).
/// Runtime VM vẫn nhận Vec<Rule>; dependencies dùng cho metadata / gợi ý AI.
#[derive(Debug, Clone, Default, Serialize, Deserialize)]
pub struct RuleGraph {
    pub rules: Vec<Rule>,
    /// (from_rule_name, to_rule_name): effect có thể được gợi ý sau khi trigger fire.
    pub dependencies: Vec<(String, String)>,
}

impl RuleGraph {
    /// Trả về danh sách rule để nạp vào VM (thứ tự giữ nguyên).
    pub fn to_rules(&self) -> &[Rule] {
        &self.rules
    }

    /// Index path → danh sách tên rule đọc path đó (từ when + chance).
    /// Dùng cho tooling / API hoặc sau này chỉ eval rule có path giao với state đổi.
    pub fn path_index(&self) -> HashMap<String, Vec<String>> {
        let mut index: HashMap<String, Vec<String>> = HashMap::new();
        for rule in &self.rules {
            let mut paths = condition_expr_paths(&rule.when);
            paths.extend(expr_paths(&rule.chance));
            for path in paths {
                index.entry(path).or_default().push(rule.name.clone());
            }
        }
        index
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn path_index_collects_when_and_chance_paths() {
        let rule = Rule {
            name: "test_rule".to_string(),
            priority: 0,
            cooldown_ticks: None,
            scope: None,
            when: ConditionExpr::Comparison {
                left: Expr::Path("entropy".to_string()),
                op: Op::Gt,
                right: Expr::ConstFloat(0.5),
            },
            chance: Expr::FunctionCall {
                name: "sigmoid".to_string(),
                args: vec![Expr::Path("stability_index".to_string())],
            },
            actions: vec![],
        };
        let graph = RuleGraph {
            rules: vec![rule],
            dependencies: vec![],
        };
        let index = graph.path_index();
        assert!(index.get("entropy").map(|v| v.as_slice()) == Some(&["test_rule".to_string()][..]));
        assert!(index.get("stability_index").map(|v| v.as_slice()) == Some(&["test_rule".to_string()][..]));
    }

    #[test]
    fn expr_paths_collects_nested_paths() {
        let expr = Expr::Add(
            Box::new(Expr::Path("a".to_string())),
            Box::new(Expr::Mul(
                Box::new(Expr::Path("b".to_string())),
                Box::new(Expr::ConstFloat(2.0)),
            )),
        );
        let paths = expr_paths(&expr);
        assert!(paths.contains("a"));
        assert!(paths.contains("b"));
        assert_eq!(paths.len(), 2);
    }
}
