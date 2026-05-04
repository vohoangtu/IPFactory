fn main() -> Result<(), Box<dyn std::error::Error>> {
    tonic_build::configure()
        .type_attribute(".", "#[derive(serde::Serialize, serde::Deserialize)]")
        .build_server(true)
        .build_client(false)
        .compile_protos(&["../proto/worldos/simulation.proto"], &["../proto"])?;
    Ok(())
}
