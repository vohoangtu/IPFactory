//! WorldOS Rule Engine: parse DSL rules and evaluate against world state.
//!
//! Pipeline: DSL text → AST (Rule) → evaluate(state) → events/actions.

mod ast;
mod eval;
mod parse;
pub mod wasm_eval;

pub use ast::{condition_expr_paths, expr_paths, Action, Condition, ConditionExpr, ConditionValue, Expr, Op, Rule, RuleGraph};
pub use eval::{evaluate_rules, RuleOutput, RuleVm};
pub use parse::{parse_rules, parse_rules_to_graph};
