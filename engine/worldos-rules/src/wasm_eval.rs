//! WASM Rule Evaluator: execute WebAssembly rules with world context.
use wasmtime::*;
use serde_json::Value;
use crate::eval::{RuleOutput, get_path};
use std::collections::HashMap;

pub struct WasmRuleStore {
    pub state: Value,
    pub outputs: Vec<RuleOutput>,
}

pub struct WasmRuleEvaluator {
    engine: Engine,
    module: Module,
}

impl WasmRuleEvaluator {
    pub fn new(wasm_bytes: &[u8]) -> anyhow::Result<Self> {
        let engine = Engine::default();
        let module = Module::from_binary(&engine, wasm_bytes)?;
        Ok(Self { engine, module })
    }

    pub fn evaluate(&self, state: &Value) -> anyhow::Result<Vec<RuleOutput>> {
        let mut store = Store::new(&self.engine, WasmRuleStore {
            state: state.clone(),
            outputs: Vec::new(),
        });

        let mut linker = Linker::new(&self.engine);

        // Host Function: get_state_float(path_ptr, path_len) -> f64
        linker.func_wrap("env", "get_state_float", |mut caller: Caller<'_, WasmRuleStore>, ptr: u32, len: u32| {
            let mem = match caller.get_export("memory") {
                Some(Extern::Memory(m)) => m,
                _ => return 0.0,
            };
            let data = mem.data(&caller);
            let start = ptr as usize;
            let end = start.saturating_add(len as usize);
            if end > data.len() {
                return 0.0;
            }
            let path = std::str::from_utf8(&data[start..end]).unwrap_or("");
            let val = get_path(&caller.data().state, path);
            val.and_then(|v| v.as_f64()).unwrap_or(0.0)
        })?;

        // Host Function: emit_event(name_ptr, name_len)
        linker.func_wrap("env", "emit_event", |mut caller: Caller<'_, WasmRuleStore>, ptr: u32, len: u32| {
            let mem = match caller.get_export("memory") {
                Some(Extern::Memory(m)) => m,
                _ => return,
            };
            let data = mem.data(&caller);
            let start = ptr as usize;
            let end = start.saturating_add(len as usize);
            if end > data.len() {
                return;
            }
            let name = std::str::from_utf8(&data[start..end]).unwrap_or("UNKNOWN").to_string();
            caller.data_mut().outputs.push(RuleOutput::Event { name, payload: HashMap::new() });
        })?;

        // Host Function: adjust_stability(delta)
        linker.func_wrap("env", "adjust_stability", |mut caller: Caller<'_, WasmRuleStore>, delta: f64| {
            caller.data_mut().outputs.push(RuleOutput::AdjustStability { delta });
        })?;

        let instance = linker.instantiate(&mut store, &self.module)?;
        let run = instance.get_typed_func::<(), ()>(&mut store, "run_rule")?;
        
        run.call(&mut store, ())?;

        Ok(store.into_data().outputs)
    }
}
