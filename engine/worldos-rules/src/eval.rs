//! Evaluate rules against world state (JSON).
//!
//! State is read via path. Output: events and state adjustments.
//! v2: Expr, ConditionExpr, priority, cooldown, Add/Set/SpawnActor.

use crate::ast::{condition_expr_paths, expr_paths, Action, ConditionExpr, Expr, Op, Rule};
use rand::Rng;
use serde::{Deserialize, Serialize};
use serde_json::Value;
use std::collections::{HashMap, HashSet};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum RuleOutput {
    Event { name: String, payload: HashMap<String, Value> },
    AdjustStability { delta: f64 },
    AdjustEntropy { delta: f64 },
    AddPath { path: String, delta: f64 },
    SetPath { path: String, value: Value },
    Decay { path: String, factor: f64 },
    Pressure { name: String, op: String, value: f64 },
    SpawnActor { kind: String },
    Drift { path: String, target: Option<f64>, speed: Option<f64> },
    Calc { name: String, value: Value },
    Metadata { key: String, value: String },
}

/// Get value at path in JSON.
pub(crate) fn get_path(state: &Value, path: &str) -> Option<Value> {
    let mut current = state;
    for part in path.split('.') {
        current = match part {
            "" => continue,
            key if key.contains('[') => {
                let (key_name, rest) = key.split_once('[')?;
                let idx: usize = rest.trim_end_matches(']').parse().ok()?;
                let obj = current.get(key_name)?;
                obj.get(idx)?
            }
            key => current.get(key)?,
        };
    }
    Some(current.clone())
}

fn as_f64(v: &Value) -> Option<f64> {
    match v {
        Value::Number(n) => n.as_f64(),
        _ => None,
    }
}

fn as_str(v: &Value) -> Option<&str> {
    v.as_str()
}

pub fn eval_expr(state: &Value, locals: &HashMap<String, Value>, expr: &Expr, mut rng: Option<&mut impl Rng>) -> Value {
    match expr {
        Expr::ConstFloat(f) => Value::Number(serde_json::Number::from_f64(*f).unwrap_or(serde_json::Number::from(0))),
        Expr::ConstInt(i) => Value::Number(serde_json::Number::from(*i)),
        Expr::ConstStr(s) => Value::String(s.clone()),
        Expr::Path(path) => locals.get(path).cloned().unwrap_or_else(|| get_path(state, path).unwrap_or(Value::Null)),
        Expr::Add(a, b) => {
            let x = as_f64(&eval_expr(state, locals, a, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            let y = as_f64(&eval_expr(state, locals, b, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            Value::Number(serde_json::Number::from_f64(x + y).unwrap_or(serde_json::Number::from(0)))
        }
        Expr::Sub(a, b) => {
            let x = as_f64(&eval_expr(state, locals, a, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            let y = as_f64(&eval_expr(state, locals, b, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            Value::Number(serde_json::Number::from_f64(x - y).unwrap_or(serde_json::Number::from(0)))
        }
        Expr::Mul(a, b) => {
            let x = as_f64(&eval_expr(state, locals, a, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            let y = as_f64(&eval_expr(state, locals, b, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            Value::Number(serde_json::Number::from_f64(x * y).unwrap_or(serde_json::Number::from(0)))
        }
        Expr::Div(a, b) => {
            let x = as_f64(&eval_expr(state, locals, a, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            let y = as_f64(&eval_expr(state, locals, b, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
            let v = if y.abs() < 1e-12 { 0.0 } else { x / y };
            Value::Number(serde_json::Number::from_f64(v).unwrap_or(serde_json::Number::from(0)))
        }
        Expr::FunctionCall { name, args } => {
            let n = name.to_lowercase();
            if n == "sigmoid" && args.len() == 1 {
                let x = as_f64(&eval_expr(state, locals, &args[0], rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
                let v = 1.0 / (1.0 + (-x).exp());
                return Value::Number(serde_json::Number::from_f64(v).unwrap_or(serde_json::Number::from(0)));
            }
            if n == "clamp" && (args.len() == 1 || args.len() == 3) {
                let x = as_f64(&eval_expr(state, locals, &args[0], rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
                let (lo, hi) = if args.len() == 3 {
                    (
                        as_f64(&eval_expr(state, locals, &args[1], rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0),
                        as_f64(&eval_expr(state, locals, &args[2], rng.as_mut().map(|r| &mut **r))).unwrap_or(1.0),
                    )
                } else {
                    (0.0, 1.0)
                };
                let v = x.clamp(lo, hi);
                return Value::Number(serde_json::Number::from_f64(v).unwrap_or(serde_json::Number::from(0)));
            }
            if n == "random" && args.is_empty() {
                let v = rng.as_mut().map(|r| r.gen::<f64>()).unwrap_or(0.5);
                return Value::Number(serde_json::Number::from_f64(v).unwrap_or(serde_json::Number::from(0)));
            }
            if n == "count" && args.len() == 1 {
                if let Expr::Path(path) = &args[0] {
                    if let Some(val) = get_path(state, path) {
                        let count = match val {
                            Value::Array(a) => a.len(),
                            Value::Object(o) => o.len(),
                            _ => 0,
                        };
                        return Value::Number(serde_json::Number::from(count));
                    }
                }
                return Value::Number(serde_json::Number::from(0));
            }
            if n == "has_scar" && args.len() == 1 {
                let scar_name = match eval_expr(state, locals, &args[0], rng.as_mut().map(|r| &mut **r)) {
                    Value::String(s) => s,
                    _ => return Value::Bool(false),
                };
                if let Some(scars) = get_path(state, "scars") {
                    if let Some(obj) = scars.as_object() {
                        return Value::Bool(obj.contains_key(&scar_name));
                    }
                }
                return Value::Bool(false);
            }
            if n == "get_trait" && args.len() == 1 {
                let idx = as_f64(&eval_expr(state, locals, &args[0], rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0) as usize;
                if let Some(traits) = get_path(state, "meta.traits") {
                    if let Some(arr) = traits.as_array() {
                        return arr.get(idx).cloned().unwrap_or(Value::Null);
                    }
                }
                return Value::Null;
            }
            if n == "saturate" && args.len() == 1 {
                let x = as_f64(&eval_expr(state, locals, &args[0], rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0);
                return Value::Number(serde_json::Number::from_f64(x.clamp(0.0, 1.0)).unwrap_or(serde_json::Number::from(0)));
            }
            Value::Null
        }
    }
}

fn eval_expr_to_f64(state: &Value, locals: &HashMap<String, Value>, expr: &Expr, mut rng: Option<&mut impl Rng>) -> f64 {
    as_f64(&eval_expr(state, locals, expr, rng.as_mut().map(|r| &mut **r))).unwrap_or(0.0)
}

pub fn eval_condition_expr(state: &Value, locals: &HashMap<String, Value>, cond: &ConditionExpr) -> bool {
    match cond {
        ConditionExpr::Comparison { left, op, right } => {
            let l = eval_expr(state, locals, left, None::<&mut rand::rngs::StdRng>);
            let r = eval_expr(state, locals, right, None::<&mut rand::rngs::StdRng>);
            let a = as_f64(&l).unwrap_or(0.0);
            let b = as_f64(&r).unwrap_or(0.0);
            let s_left = as_str(&l);
            let s_right = as_str(&r);
            if s_left.is_some() || s_right.is_some() {
                match op {
                    Op::Eq => s_left.unwrap_or("") == s_right.unwrap_or(""),
                    Op::Ne => s_left.unwrap_or("") != s_right.unwrap_or(""),
                    _ => false,
                }
            } else {
                match op {
                    Op::Lt => a < b,
                    Op::Le => a <= b,
                    Op::Gt => a > b,
                    Op::Ge => a >= b,
                    Op::Eq => (a - b).abs() < 1e-9,
                    Op::Ne => (a - b).abs() >= 1e-9,
                }
            }
        }
        ConditionExpr::And(a, b) => eval_condition_expr(state, locals, a) && eval_condition_expr(state, locals, b),
        ConditionExpr::Or(a, b) => eval_condition_expr(state, locals, a) || eval_condition_expr(state, locals, b),
        ConditionExpr::Not(x) => !eval_condition_expr(state, locals, x),
    }
}

#[derive(Debug, Default)]
pub struct RuleVm {
    rules: Vec<Rule>,
    last_fired: HashMap<String, u64>,
    /// Index path → rule names (from last load_rules); dùng khi evaluate với changed_paths.
    path_index: Option<HashMap<String, Vec<String>>>,
}

impl RuleVm {
    pub fn new() -> Self {
        Self { rules: Vec::new(), last_fired: HashMap::new(), path_index: None }
    }

    pub fn load_rules(&mut self, dsl: &str) -> Result<(), crate::parse::ParseError> {
        let graph = crate::parse::parse_rules_to_graph(dsl)?;
        self.path_index = Some(graph.path_index());
        self.rules.extend(graph.to_rules().iter().cloned());
        Ok(())
    }

    pub fn add_rule(&mut self, rule: Rule) {
        self.rules.push(rule);
        self.path_index = None;
    }

    /// Evaluate rules. If `changed_paths` is `Some`, only rules that read at least one path in the set are evaluated; otherwise all rules are evaluated.
    pub fn evaluate(
        &mut self,
        state: &Value,
        current_tick: u64,
        mut rng: Option<&mut impl Rng>,
        changed_paths: Option<&HashSet<String>>,
    ) -> Vec<RuleOutput> {
        let mut out = Vec::new();
        let rules_to_eval: Vec<&Rule> = match changed_paths {
            None => self.rules.iter().collect(),
            Some(paths) => {
                let mut names = HashSet::new();
                if let Some(ref index) = self.path_index {
                    for p in paths.iter() {
                        if let Some(rule_names) = index.get(p) {
                            names.extend(rule_names.iter().cloned());
                        }
                    }
                } else {
                    for rule in &self.rules {
                        let mut rule_paths = condition_expr_paths(&rule.when);
                        rule_paths.extend(expr_paths(&rule.chance));
                        if rule_paths.iter().any(|p| paths.contains(p)) {
                            names.insert(rule.name.clone());
                        }
                    }
                }
                self.rules.iter().filter(|r| names.contains(&r.name)).collect()
            }
        };
        let mut rules_sorted: Vec<&Rule> = rules_to_eval;
        rules_sorted.sort_by(|a, b| b.priority.cmp(&a.priority));

        for rule in rules_sorted {
            if let Some(cd) = rule.cooldown_ticks {
                if let Some(&last) = self.last_fired.get(&rule.name) {
                    if current_tick.saturating_sub(last) < cd {
                        continue;
                    }
                }
            }

            let mut locals = HashMap::new();
            let ok = eval_condition_expr(state, &locals, &rule.when);
            if !ok {
                continue;
            }

            let chance_val = eval_expr_to_f64(state, &locals, &rule.chance, rng.as_mut().map(|r| &mut **r)).clamp(0.0, 1.0);
            let fire = match rng.as_mut() {
                Some(r) => r.gen::<f64>() < chance_val,
                None => chance_val >= 1.0,
            };
            if !fire {
                continue;
            }

            self.last_fired.insert(rule.name.clone(), current_tick);

            for action in &rule.actions {
                match action {
                    Action::EmitEvent(name) => out.push(RuleOutput::Event { name: name.clone(), payload: HashMap::new() }),
                    Action::AdjustStability(delta) => out.push(RuleOutput::AdjustStability { delta: *delta }),
                    Action::AdjustEntropy(delta) => out.push(RuleOutput::AdjustEntropy { delta: *delta }),
                    Action::Add { path, value } => {
                        let delta = eval_expr_to_f64(state, &locals, value, rng.as_mut().map(|r| &mut **r));
                        let current_val = eval_expr_to_f64(state, &locals, &Expr::Path(path.clone()), None::<&mut rand::rngs::StdRng>);
                        let new_val = current_val + delta;
                        locals.insert(path.clone(), Value::Number(serde_json::Number::from_f64(new_val).unwrap_or(serde_json::Number::from(0))));
                        out.push(RuleOutput::AddPath { path: path.clone(), delta });
                    }
                    Action::Set { path, value } => {
                        let v = eval_expr(state, &locals, value, rng.as_mut().map(|r| &mut **r));
                        locals.insert(path.clone(), v.clone());
                        out.push(RuleOutput::SetPath { path: path.clone(), value: v });
                    }
                    Action::Decay { path, factor } => {
                        let f = eval_expr_to_f64(state, &locals, factor, rng.as_mut().map(|r| &mut **r));
                        out.push(RuleOutput::Decay { path: path.clone(), factor: f });
                    }
                    Action::Pressure { name, op, value } => {
                        let v = eval_expr_to_f64(state, &locals, value, rng.as_mut().map(|r| &mut **r));
                        let op_str = match op {
                            crate::ast::PressureOp::Add => "add",
                            crate::ast::PressureOp::Set => "set",
                            crate::ast::PressureOp::Sub => "sub",
                        };
                        out.push(RuleOutput::Pressure { name: name.clone(), op: op_str.to_string(), value: v });
                    }
                    Action::SpawnActor { kind } => out.push(RuleOutput::SpawnActor { kind: kind.clone() }),
                    Action::Drift { path, target, speed } => {
                        let t = target.as_ref().map(|e| eval_expr_to_f64(state, &locals, e, rng.as_mut().map(|r| &mut **r)));
                        let s = speed.as_ref().map(|e| eval_expr_to_f64(state, &locals, e, rng.as_mut().map(|r| &mut **r)));
                        out.push(RuleOutput::Drift { path: path.clone(), target: t, speed: s });
                    }
                    Action::Calc { name, formula } => {
                        let v = eval_expr(state, &locals, formula, rng.as_mut().map(|r| &mut **r));
                        locals.insert(name.clone(), v.clone());
                        out.push(RuleOutput::Calc { name: name.clone(), value: v });
                    }
                    Action::Metadata { key, value } => out.push(RuleOutput::Metadata { key: key.clone(), value: value.clone() }),
                }
            }
        }
        out
    }
}

pub fn evaluate_rules(dsl: &str, state: &Value, rng: Option<&mut impl Rng>) -> Result<Vec<RuleOutput>, crate::parse::ParseError> {
    let mut vm = RuleVm::new();
    vm.load_rules(dsl)?;
    let tick = state.get("tick").and_then(|t| t.as_u64()).unwrap_or(0);
    Ok(vm.evaluate(state, tick, rng, None))
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn eval_entropy_rule() {
        let state = serde_json::json!({
            "entropy": 0.9,
            "stability_index": 0.2,
            "tick": 0
        });
        let dsl = r#"
rule chaos_high
  when
    entropy > 0.85
    stability_index < 0.3
  then
    emit_event CHAOS_SPIKE
    adjust_stability -0.1
"#;
        let out = evaluate_rules(dsl, &state, Some(&mut rand::thread_rng())).unwrap();
        assert!(!out.is_empty());
        assert!(matches!(&out[0], RuleOutput::Event { name, .. } if name == "CHAOS_SPIKE"));
        assert!(matches!(&out[1], RuleOutput::AdjustStability { delta: -0.1 }));
    }

    #[test]
    fn eval_expr_and_add_path() {
        use crate::ast::{ConditionExpr, Expr, Op, Rule};
        let state = serde_json::json!({
            "entropy": 0.9,
            "tick": 0,
            "civilization": { "unrest": 0.1 }
        });
        let when = ConditionExpr::Comparison {
            left: Expr::Path("entropy".to_string()),
            op: Op::Gt,
            right: Expr::ConstFloat(0.8),
        };
        let chance = Expr::ConstFloat(1.0);
        let rule = Rule {
            name: "test".to_string(),
            priority: 0,
            cooldown_ticks: None,
            scope: None,
            when,
            chance,
            actions: vec![
                Action::EmitEvent("TEST_EVENT".to_string()),
                Action::Add { path: "civilization.unrest".to_string(), value: Expr::ConstFloat(0.3) },
            ],
        };
        let mut vm = RuleVm::new();
        vm.add_rule(rule);
        let out = vm.evaluate(&state, 0, Some(&mut rand::thread_rng()), None);
        assert!(!out.is_empty());
        assert!(matches!(&out[0], RuleOutput::Event { name, .. } if name == "TEST_EVENT"));
        assert!(matches!(&out[1], RuleOutput::AddPath { path: p, delta: 0.3 } if p == "civilization.unrest"));
    }

    #[test]
    fn evaluate_with_changed_paths_only_evals_touching_rules() {
        use crate::ast::{ConditionExpr, Expr, Op, Rule};
        let state = serde_json::json!({
            "entropy": 0.9,
            "legitimacy": 0.2,
            "tick": 0
        });
        let rule_a = Rule {
            name: "uses_entropy".to_string(),
            priority: 0,
            cooldown_ticks: None,
            scope: None,
            when: ConditionExpr::Comparison {
                left: Expr::Path("entropy".to_string()),
                op: Op::Gt,
                right: Expr::ConstFloat(0.5),
            },
            chance: Expr::ConstFloat(1.0),
            actions: vec![Action::EmitEvent("A".to_string())],
        };
        let rule_b = Rule {
            name: "uses_legitimacy".to_string(),
            priority: 0,
            cooldown_ticks: None,
            scope: None,
            when: ConditionExpr::Comparison {
                left: Expr::Path("legitimacy".to_string()),
                op: Op::Lt,
                right: Expr::ConstFloat(0.5),
            },
            chance: Expr::ConstFloat(1.0),
            actions: vec![Action::EmitEvent("B".to_string())],
        };
        let mut vm = RuleVm::new();
        vm.add_rule(rule_a);
        vm.add_rule(rule_b);
        let mut only_entropy = HashSet::new();
        only_entropy.insert("entropy".to_string());
        let out = vm.evaluate(&state, 0, Some(&mut rand::thread_rng()), Some(&only_entropy));
        assert_eq!(out.len(), 1);
        assert!(matches!(&out[0], RuleOutput::Event { name, .. } if name == "A"));
        let mut only_legitimacy = HashSet::new();
        only_legitimacy.insert("legitimacy".to_string());
        let out2 = vm.evaluate(&state, 0, Some(&mut rand::thread_rng()), Some(&only_legitimacy));
        assert_eq!(out2.len(), 1);
        assert!(matches!(&out2[0], RuleOutput::Event { name, .. } if name == "B"));
    }

    #[test]
    fn eval_set_action() {
        use crate::ast::{ConditionExpr, Expr, Op, Rule, Action};
        let state = serde_json::json!({
            "entropy": 0.9,
            "tick": 0,
            "civilization": { "openness": 0.2 }
        });
        let rule = Rule {
            name: "open_up".to_string(),
            priority: 0,
            cooldown_ticks: None,
            scope: None,
            when: ConditionExpr::Comparison {
                left: Expr::Path("entropy".to_string()),
                op: Op::Gt,
                right: Expr::ConstFloat(0.5),
            },
            chance: Expr::ConstFloat(1.0),
            actions: vec![
                Action::Set { path: "civilization.openness".to_string(), value: Expr::ConstFloat(0.8) },
            ],
        };
        let mut vm = RuleVm::new();
        vm.add_rule(rule);
        let out = vm.evaluate(&state, 0, Some(&mut rand::thread_rng()), None);
        assert!(!out.is_empty());
        assert!(matches!(&out[0], RuleOutput::SetPath { path: p, value } if p == "civilization.openness" && value.as_f64() == Some(0.8)));
    }
}
