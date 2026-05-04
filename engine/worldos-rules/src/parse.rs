//! Parser cho DSL rule (một mô hình duy nhất).
//!
//! Rule: name, priority, cooldown, scope; when (ConditionExpr, nhiều dòng = AND; dòng "or" = OR với điều kiện trước; dòng "not" + dòng sau = NOT(điều kiện)); chance (Expr); then actions.

use crate::ast::{Action, Condition, ConditionExpr, ConditionValue, Expr, Op, Rule, RuleGraph};
use thiserror::Error;

#[derive(Error, Debug)]
pub enum ParseError {
    #[error("expected rule name after 'rule'")]
    MissingRuleName,
    #[error("expected 'when' block")]
    MissingWhen,
    #[error("expected 'then' block")]
    MissingThen,
    #[error("invalid condition line: {0}")]
    InvalidCondition(String),
    #[error("invalid action line: {0}")]
    InvalidAction(String),
    #[error("invalid number: {0}")]
    InvalidNumber(String),
    #[error("invalid expression: {0}")]
    InvalidExpr(String),
}

#[allow(dead_code)]
fn condition_to_expr(c: &Condition) -> ConditionExpr {
    let right = match &c.value {
        ConditionValue::Float(f) => Expr::ConstFloat(*f),
        ConditionValue::Int(i) => Expr::ConstInt(*i),
        ConditionValue::Str(s) => Expr::ConstStr(s.clone()),
    };
    ConditionExpr::Comparison {
        left: Expr::Path(c.path.clone()),
        op: c.op,
        right,
    }
}

/// Tìm vị trí so sánh ngoài ngoặc (trái nhất). Ops: >=, <=, ==, !=, >, < (dài trước).
fn find_comparison_op(line: &str) -> Option<(usize, usize, Op)> {
    let ops: &[(&str, Op)] = &[
        (">=", Op::Ge),
        ("<=", Op::Le),
        ("==", Op::Eq),
        ("!=", Op::Ne),
        (">", Op::Gt),
        ("<", Op::Lt),
    ];
    let mut depth = 0u32;
    let mut i = 0;
    let bytes = line.as_bytes();
    while i < bytes.len() {
        let c = bytes[i];
        if c == b'(' || c == b'[' {
            depth = depth.saturating_add(1);
            i += 1;
            continue;
        }
        if c == b')' || c == b']' {
            depth = depth.saturating_sub(1);
            i += 1;
            continue;
        }
        if depth == 0 {
            for (op_str, op) in ops {
                if line[i..].starts_with(op_str) {
                    return Some((i, op_str.len(), *op));
                }
            }
        }
        i += 1;
    }
    None
}

/// Parse một dòng when thành ConditionExpr (expr op expr). Hỗ trợ biểu thức hai bên.
fn parse_condition_line(line: &str) -> Result<ConditionExpr, ParseError> {
    let (pos, len, op) = find_comparison_op(line)
        .ok_or_else(|| ParseError::InvalidCondition(line.to_string()))?;
    let left_str = line[..pos].trim();
    let right_str = line[pos + len..].trim();
    if left_str.is_empty() || right_str.is_empty() {
        return Err(ParseError::InvalidCondition(line.to_string()));
    }
    let left = parse_expr(left_str)?;
    let right = parse_expr(right_str)?;
    Ok(ConditionExpr::Comparison { left, op, right })
}

#[allow(dead_code)]
fn conditions_to_when(conditions: &[Condition]) -> ConditionExpr {
    let mut it = conditions.iter();
    let first = it.next().map(condition_to_expr).expect("at least one condition");
    it.fold(first, |acc, c| ConditionExpr::And(Box::new(acc), Box::new(condition_to_expr(c))))
}

/// Gộp nhiều ConditionExpr thành And(..., ...).
fn condition_exprs_to_and(exprs: Vec<ConditionExpr>) -> ConditionExpr {
    let mut it = exprs.into_iter();
    let first = it.next().expect("at least one condition");
    it.fold(first, |acc, c| ConditionExpr::And(Box::new(acc), Box::new(c)))
}

/// Parse một rule từ các dòng (không có dòng "rule name"; name truyền vào).
fn parse_one_rule(name: &str, lines: &[&str]) -> Result<Rule, ParseError> {
    let mut condition_exprs = Vec::new();
    let mut pending_or = false;
    let mut pending_not = false;
    let mut chance_expr: Option<Expr> = None;
    let mut actions = Vec::new();
    let mut phase: Option<&str> = None;
    let mut priority: u32 = 0;
    let mut cooldown_ticks: Option<u64> = None;
    let mut scope: Option<String> = None;

    let mut pending_calc: Option<String> = None;

    for line in lines {
        let mut line = line.trim();
        if line.is_empty() || line.starts_with('#') || line.starts_with("//") {
            continue;
        }
        // Allow optional semicolon at the end
        if line.ends_with(';') {
            line = line[..line.len()-1].trim();
        }
        let lower = line.to_lowercase();
        if lower == "when" {
            phase = Some("when");
            continue;
        }
        if lower == "then" {
            phase = Some("then");
            continue;
        }
        if lower.starts_with("priority") {
            let rest = line[8..].trim();
            priority = rest.parse().map_err(|_| ParseError::InvalidNumber(rest.to_string()))?;
            continue;
        }
        if lower.starts_with("cooldown") {
            let rest = line[8..].trim();
            if rest.ends_with('y') {
                let y: u64 = rest.trim_end_matches('y').trim().parse().map_err(|_| ParseError::InvalidNumber(rest.to_string()))?;
                cooldown_ticks = Some(y.saturating_mul(365));
            } else {
                cooldown_ticks = Some(rest.parse().map_err(|_| ParseError::InvalidNumber(rest.to_string()))?);
            }
            continue;
        }
        if lower.starts_with("scope") {
            scope = Some(line[5..].trim().to_string());
            continue;
        }
        if lower.starts_with("chance") {
            let rest = line[6..].trim();
            if rest.contains('(') || rest.contains('+') || rest.contains('-') || rest.contains('*') || rest.contains('/') {
                chance_expr = Some(parse_expr(rest.trim_matches('"'))?);
            } else if let Ok(n) = rest.parse::<f64>() {
                chance_expr = Some(Expr::ConstFloat(n.clamp(0.0, 1.0)));
            } else {
                return Err(ParseError::InvalidExpr(rest.to_string()));
            }
            continue;
        }

        match phase {
            Some("when") => {
                if lower == "or" {
                    if condition_exprs.is_empty() {
                        return Err(ParseError::InvalidCondition("or without previous condition".to_string()));
                    }
                    pending_or = true;
                } else if lower == "not" {
                    pending_not = true;
                } else {
                    let cond = parse_condition_line(line)?;
                    let cond = if pending_not {
                        pending_not = false;
                        ConditionExpr::Not(Box::new(cond))
                    } else {
                        cond
                    };
                    if pending_or {
                        let prev = condition_exprs.pop().ok_or_else(|| ParseError::InvalidCondition("or without previous condition".to_string()))?;
                        condition_exprs.push(ConditionExpr::Or(Box::new(prev), Box::new(cond)));
                        pending_or = false;
                    } else {
                        condition_exprs.push(cond);
                    }
                }
            }
            Some("then") => {
                if lower.starts_with("calc ") {
                    let cname = line[5..].trim().to_string();
                    pending_calc = Some(cname);
                    continue;
                }
                if lower.starts_with("formula ") {
                    if let Some(cname) = pending_calc.take() {
                        let expr_str = line[8..].trim().trim_matches('"');
                        let expr = parse_expr(expr_str)?;
                        actions.push(Action::Calc { name: cname, formula: expr });
                        continue;
                    } else {
                        return Err(ParseError::InvalidAction("formula without calc".to_string()));
                    }
                }
                if pending_calc.is_some() {
                    pending_calc = None; // Reset if next line is not formula
                }
                let act = parse_action(line)?;
                actions.push(act);
            }
            _ => {}
        }
    }

    if pending_or {
        return Err(ParseError::InvalidCondition("or without following condition".to_string()));
    }
    if pending_not {
        return Err(ParseError::InvalidCondition("not without following condition".to_string()));
    }
    if condition_exprs.is_empty() {
        return Err(ParseError::MissingWhen);
    }
    if actions.is_empty() {
        return Err(ParseError::MissingThen);
    }
    let chance = chance_expr.unwrap_or(Expr::ConstFloat(1.0));
    let when = condition_exprs_to_and(condition_exprs);
    Ok(Rule {
        name: name.to_string(),
        priority,
        cooldown_ticks,
        scope,
        when,
        chance,
        actions,
    })
}

#[allow(dead_code)]
fn parse_condition(line: &str) -> Result<Condition, ParseError> {
    let parts: Vec<&str> = line.split_whitespace().collect();
    if parts.len() < 3 {
        return Err(ParseError::InvalidCondition(line.to_string()));
    }
    let path = parts[0].to_string();
    let op = match parts[1] {
        "<" => Op::Lt,
        "<=" => Op::Le,
        ">" => Op::Gt,
        ">=" => Op::Ge,
        "==" => Op::Eq,
        "!=" => Op::Ne,
        _ => return Err(ParseError::InvalidCondition(line.to_string())),
    };
    let value_str = parts[2..].join(" ").trim_matches('"').to_string();
    let value = if let Ok(n) = value_str.parse::<f64>() {
        ConditionValue::Float(n)
    } else if let Ok(n) = value_str.parse::<i64>() {
        ConditionValue::Int(n)
    } else {
        ConditionValue::Str(value_str)
    };
    Ok(Condition { path, op, value })
}

fn parse_action(line: &str) -> Result<Action, ParseError> {
    let lower = line.to_lowercase();
    if lower.starts_with("emit_event") {
        let name = line[10..].trim().to_string(); // "emit_event" = 10
        return Ok(Action::EmitEvent(name));
    }
    if lower.starts_with("adjust_stability") {
        let rest = line[16..].trim(); // "adjust_stability" = 16
        let delta: f64 = rest.parse().map_err(|_| ParseError::InvalidNumber(rest.to_string()))?;
        return Ok(Action::AdjustStability(delta));
    }
    if lower.starts_with("adjust_entropy") {
        let rest = line[14..].trim(); // "adjust_entropy" = 14
        let delta: f64 = rest.parse().map_err(|_| ParseError::InvalidNumber(rest.to_string()))?;
        return Ok(Action::AdjustEntropy(delta));
    }
    if lower.starts_with("add") {
        let rest = line[3..].trim(); // "add" = 3
        let parts: Vec<&str> = rest.splitn(2, char::is_whitespace).map(|s| s.trim()).filter(|s| !s.is_empty()).collect();
        if parts.len() < 2 {
            return Err(ParseError::InvalidAction(line.to_string()));
        }
        let path = parts[0].to_string();
        let mut value_str = parts[1].trim();
        if value_str.starts_with('=') {
            value_str = value_str[1..].trim();
        }
        let value = parse_expr(value_str)?;
        return Ok(Action::Add { path, value });
    }
    if lower.starts_with("set") {
        let rest = line[3..].trim(); // "set" = 3
        let parts: Vec<&str> = rest.splitn(2, char::is_whitespace).map(|s| s.trim()).filter(|s| !s.is_empty()).collect();
        if parts.len() < 2 {
            return Err(ParseError::InvalidAction(line.to_string()));
        }
        let path = parts[0].to_string();
        let mut value_str = parts[1].trim_matches('"').trim_matches('\'').trim();
        if value_str.starts_with('=') {
            value_str = value_str[1..].trim();
        }
        let value = parse_expr(value_str)?;
        return Ok(Action::Set { path, value });
    }
    if lower.starts_with("decay ") {
        let rest = line[6..].trim();
        let parts: Vec<&str> = rest.splitn(3, char::is_whitespace).map(|s| s.trim()).filter(|s| !s.is_empty()).collect();
        if parts.len() >= 3 && parts[1].to_lowercase() == "factor" {
            let path = parts[0].to_string();
            let mut val_str = parts[2].trim();
            if val_str.starts_with('=') {
                val_str = val_str[1..].trim();
            }
            let factor = parse_expr(val_str)?;
            return Ok(Action::Decay { path, factor });
        }
    }
    if lower.starts_with("pressure ") {
        let rest = line[9..].trim();
        let parts: Vec<&str> = rest.splitn(3, char::is_whitespace).map(|s| s.trim()).filter(|s| !s.is_empty()).collect();
        if parts.len() >= 3 {
            let name = parts[0].trim_matches('"').trim_matches('\'').to_string();
            let op = match parts[1].to_lowercase().as_str() {
                "add" => crate::ast::PressureOp::Add,
                "set" => crate::ast::PressureOp::Set,
                "sub" => crate::ast::PressureOp::Sub,
                _ => return Err(ParseError::InvalidAction(line.to_string())),
            };
            let value = parse_expr(parts[2])?;
            return Ok(Action::Pressure { name, op, value });
        }
    }
    if lower.starts_with("spawn_actor") {
        let kind = line[11..].trim().to_string(); // "spawn_actor" = 11
        return Ok(Action::SpawnActor { kind });
    }
    if lower.starts_with("drift ") {
        let rest = line[6..].trim();
        if let Some(by_idx) = rest.find(" by ") {
            let path = rest[..by_idx].trim().to_string();
            let expr = parse_expr(rest[by_idx + 4..].trim().trim_matches('"'))?;
            return Ok(Action::Drift { path, target: None, speed: Some(expr) });
        } else if let Some(target_idx) = rest.find(" target ") {
            let path = rest[..target_idx].trim().to_string();
            let after_target = rest[target_idx + 8..].trim();
            if let Some(speed_idx) = after_target.find(" speed ") {
                let target_expr = parse_expr(after_target[..speed_idx].trim().trim_matches('"'))?;
                let speed_expr = parse_expr(after_target[speed_idx + 7..].trim().trim_matches('"'))?;
                return Ok(Action::Drift { path, target: Some(target_expr), speed: Some(speed_expr) });
            }
        }
    }
    if lower.starts_with("metadata ") {
        let rest = line[9..].trim();
        let parts: Vec<&str> = rest.split_whitespace().collect();
        if parts.len() >= 2 {
            let key = parts[0].to_string();
            let val = parts[1..].join(" ").trim_matches('"').to_string();
            return Ok(Action::Metadata { key, value: val });
        }
    }
    Err(ParseError::InvalidAction(line.to_string()))
}

/// Tìm toán tử nhị phân ngoài ngoặc, ưu tiên thấp nhất (+, - rồi *, /), lấy vị trí phải nhất.
fn find_rightmost_binary_op(s: &str) -> Option<(usize, u8)> {
    let mut depth = 0i32;
    let bytes = s.as_bytes();
    let mut add_sub = None;
    let mut mul_div = None;
    for (i, &c) in bytes.iter().enumerate() {
        match c {
            b'(' | b'[' => depth += 1,
            b')' | b']' => depth -= 1,
            _ if depth == 0 => {
                if c == b'+' || (c == b'-' && i > 0 && bytes[i - 1] != b'(' && bytes[i - 1] != b'[' && bytes[i - 1] != b',' && bytes[i - 1] != b'*' && bytes[i - 1] != b'/') {
                    add_sub = Some((i, c));
                } else if c == b'*' || c == b'/' {
                    mul_div = Some((i, c));
                }
            }
            _ => {}
        }
    }
    add_sub.or(mul_div)
}

/// Parse expression: number, quoted string, path, func(...), ( expr ), hoặc binary + - * /.
fn parse_expr(s: &str) -> Result<Expr, ParseError> {
    let s = s.trim();
    if s.is_empty() {
        return Err(ParseError::InvalidExpr(s.to_string()));
    }
    if s.starts_with('(') && s.ends_with(')') {
        return parse_expr(s[1..s.len() - 1].trim());
    }
    if let Some((pos, op)) = find_rightmost_binary_op(s) {
        let left_str = s[..pos].trim();
        let right_str = s[pos + 1..].trim();
        if !left_str.is_empty() && !right_str.is_empty() {
            let left = parse_expr(left_str)?;
            let right = parse_expr(right_str)?;
            return Ok(match op {
                b'+' => Expr::Add(Box::new(left), Box::new(right)),
                b'-' => Expr::Sub(Box::new(left), Box::new(right)),
                b'*' => Expr::Mul(Box::new(left), Box::new(right)),
                b'/' => Expr::Div(Box::new(left), Box::new(right)),
                _ => return Err(ParseError::InvalidExpr(s.to_string())),
            });
        }
    }
    if s.starts_with('-') && s.len() > 1 {
        let rest = s[1..].trim();
        if !rest.is_empty() {
            if let Ok(n) = rest.parse::<f64>() {
                return Ok(Expr::ConstFloat(-n));
            }
            if let Ok(n) = rest.parse::<i64>() {
                return Ok(Expr::ConstInt(-n));
            }
            let inner = parse_expr(rest)?;
            return Ok(Expr::Sub(Box::new(Expr::ConstFloat(0.0)), Box::new(inner)));
        }
    }
    if s.starts_with('"') && s.ends_with('"') && s.len() >= 2 {
        return Ok(Expr::ConstStr(s[1..s.len() - 1].to_string()));
    }
    if s.starts_with('\'') && s.ends_with('\'') && s.len() >= 2 {
        return Ok(Expr::ConstStr(s[1..s.len() - 1].to_string()));
    }
    if let Ok(n) = s.parse::<f64>() {
        return Ok(Expr::ConstFloat(n));
    }
    if let Ok(n) = s.parse::<i64>() {
        return Ok(Expr::ConstInt(n));
    }
    if let Some(open) = s.find('(') {
        let name = s[..open].trim().to_string();
        let rest = s[open + 1..].trim();
        let close = rest.rfind(')').ok_or_else(|| ParseError::InvalidExpr(s.to_string()))?;
        let args_str = rest[..close].trim();
        let args = if args_str.is_empty() {
            Vec::new()
        } else {
            args_str.split(',').map(|a| parse_expr(a.trim())).collect::<Result<Vec<_>, _>>()?
        };
        return Ok(Expr::FunctionCall { name, args });
    }
    if s.contains('.') || s.contains('*') || s.chars().all(|c| c.is_alphanumeric() || c == '_') {
        return Ok(Expr::Path(s.to_string()));
    }
    Err(ParseError::InvalidExpr(s.to_string()))
}

/// Parse full DSL text into a list of rules.
pub fn parse_rules(dsl: &str) -> Result<Vec<Rule>, ParseError> {
    // Strip block comments
    let mut clean_dsl = String::new();
    let mut in_block = false;
    let bytes = dsl.as_bytes();
    let mut bytes_idx = 0;
    while bytes_idx < bytes.len() {
        if !in_block && bytes_idx + 1 < bytes.len() && bytes[bytes_idx] == b'/' && bytes[bytes_idx+1] == b'*' {
            in_block = true;
            bytes_idx += 2;
            continue;
        }
        if in_block && bytes_idx + 1 < bytes.len() && bytes[bytes_idx] == b'*' && bytes[bytes_idx+1] == b'/' {
            in_block = false;
            bytes_idx += 2;
            continue;
        }
        if !in_block {
            clean_dsl.push(bytes[bytes_idx] as char);
        } else if bytes[bytes_idx] == b'\n' {
            clean_dsl.push('\n'); // preserve lines
        }
        bytes_idx += 1;
    }

    let lines: Vec<&str> = clean_dsl.lines().collect();
    let mut rules = Vec::new();
    let mut i = 0;
    while i < lines.len() {
        let line = lines[i].trim();
        if line.to_lowercase().starts_with("rule ") {
            let name = line[5..].trim().split_whitespace().next().ok_or(ParseError::MissingRuleName)?;
            let start = i + 1;
            let mut end = start;
            while end < lines.len() {
                let t = lines[end].trim().to_lowercase();
                if t.starts_with("rule ") || t == "dependencies" {
                    break;
                }
                end += 1;
            }
            let block: Vec<&str> = lines[start..end].to_vec();
            let rule = parse_one_rule(name, &block)?;
            rules.push(rule);
            i = end;
            if i < lines.len() && lines[i].trim().to_lowercase() == "dependencies" {
                i += 1;
                while i < lines.len() && !lines[i].trim().to_lowercase().starts_with("rule ") {
                    i += 1;
                }
            }
        } else {
            i += 1;
        }
    }
    Ok(rules)
}

/// Parse block "dependencies" trong DSL: các dòng dạng `from_rule -> to_rule`.
/// Trả về rỗng nếu không có block dependencies.
fn parse_dependencies(dsl: &str) -> Vec<(String, String)> {
    let lines: Vec<&str> = dsl.lines().collect();
    let mut deps = Vec::new();
    let mut in_block = false;
    for line in lines {
        let trimmed = line.trim();
        if trimmed.is_empty() || trimmed.starts_with('#') {
            continue;
        }
        let lower = trimmed.to_lowercase();
        if lower == "dependencies" {
            in_block = true;
            continue;
        }
        if in_block {
            if lower.starts_with("rule ") {
                break;
            }
            if let Some(arrow) = trimmed.find("->") {
                let from = trimmed[..arrow].trim().to_string();
                let to = trimmed[arrow + 2..].trim().to_string();
                if !from.is_empty() && !to.is_empty() {
                    deps.push((from, to));
                }
            }
        }
    }
    deps
}

/// Parse toàn bộ DSL thành RuleGraph (rules + dependencies).
/// Nếu không có block "dependencies" thì dependencies rỗng.
pub fn parse_rules_to_graph(dsl: &str) -> Result<RuleGraph, ParseError> {
    let rules = parse_rules(dsl)?;
    let dependencies = parse_dependencies(dsl);
    Ok(RuleGraph { rules, dependencies })
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::ast::Op;

    #[test]
    fn parse_single_rule() {
        let dsl = r#"
rule revolution_trigger
  when
    civilization.politics.legitimacy_aggregate < 0.3
    entropy > 0.5
  chance 0.15
  then
    emit_event REVOLUTION
"#;
        let rules = parse_rules(dsl).unwrap();
        assert_eq!(rules.len(), 1);
        assert_eq!(rules[0].name, "revolution_trigger");
        assert!(matches!(&rules[0].when, ConditionExpr::And(_, _)));
        assert!(matches!(&rules[0].chance, Expr::ConstFloat(0.15)));
        assert!(matches!(&rules[0].actions[0], Action::EmitEvent(e) if e == "REVOLUTION"));
    }

    #[test]
    fn parse_v2_priority_cooldown_add_action() {
        let dsl = r#"
rule revolt
  priority 80
  cooldown 20y
  scope civilization
  when
    legitimacy < 0.3
  chance sigmoid(entropy)
  then
    emit_event REVOLUTION
    add social_unrest 0.4
    spawn_actor revolutionary_leader
"#;
        let rules = parse_rules(dsl).unwrap();
        assert_eq!(rules.len(), 1);
        assert_eq!(rules[0].name, "revolt");
        assert_eq!(rules[0].priority, 80);
        assert_eq!(rules[0].cooldown_ticks, Some(20 * 365));
        assert_eq!(rules[0].scope.as_deref(), Some("civilization"));
        assert!(matches!(&rules[0].chance, Expr::FunctionCall { name, .. } if name == "sigmoid"));
        assert_eq!(rules[0].actions.len(), 3);
        assert!(matches!(&rules[0].actions[1], Action::Add { path: p, value: _ } if p == "social_unrest"));
        assert!(matches!(&rules[0].actions[2], Action::SpawnActor { kind: k } if k == "revolutionary_leader"));
    }

    #[test]
    fn parse_when_expr_op_expr() {
        let dsl = r#"
rule pressure_rule
  when
    entropy + stability_index > 1.0
  chance 0.5
  then
    emit_event PRESSURE
"#;
        let rules = parse_rules(dsl).unwrap();
        assert_eq!(rules.len(), 1);
        assert!(matches!(&rules[0].when, ConditionExpr::Comparison { left, op: Op::Gt, right } if matches!(left, Expr::Add(_, _))));
    }

    #[test]
    fn parse_when_or() {
        let dsl = r#"
rule legit_or_corrupt
  when
    civilization.politics.legitimacy_aggregate < 0.3
  or
    civilization.politics.corruption > 0.7
  chance 0.2
  then
    emit_event UNREST
"#;
        let rules = parse_rules(dsl).unwrap();
        assert_eq!(rules.len(), 1);
        assert!(matches!(&rules[0].when, ConditionExpr::Or(_, _)));
    }

    #[test]
    fn parse_when_not() {
        let dsl = r#"
rule stable_only
  when
  not
    entropy > 0.9
  chance 0.5
  then
    emit_event STABLE
"#;
        let rules = parse_rules(dsl).unwrap();
        assert_eq!(rules.len(), 1);
        assert!(matches!(&rules[0].when, ConditionExpr::Not(_)));
    }

    #[test]
    fn parse_rules_to_graph_with_dependencies() {
        let dsl = r#"
rule revolution_trigger
  when
    legitimacy < 0.3
  chance 0.2
  then
    emit_event REVOLUTION

rule civil_war
  when
    war_stage > 0.5
  chance 0.1
  then
    emit_event CIVIL_WAR

dependencies
  revolution_trigger -> civil_war
"#;
        let graph = parse_rules_to_graph(dsl).unwrap();
        assert_eq!(graph.rules.len(), 2);
        assert_eq!(graph.dependencies.len(), 1);
        assert_eq!(graph.dependencies[0].0, "revolution_trigger");
        assert_eq!(graph.dependencies[0].1, "civil_war");
    }

    #[test]
    fn parse_set_action() {
        let dsl = r#"
rule update_policy
  when
    entropy > 0.5
  chance 1.0
  then
    set civilization.policy.openness 0.8
"#;
        let rules = parse_rules(dsl).unwrap();
        assert_eq!(rules.len(), 1);
        assert_eq!(rules[0].name, "update_policy");
        assert!(matches!(&rules[0].actions[0], Action::Set { path, .. } if path == "civilization.policy.openness"));
    }
}
