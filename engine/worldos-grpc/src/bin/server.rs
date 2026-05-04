//! WorldOS V6: gRPC + HTTP server. gRPC on 50051, HTTP bridge on 50052.
//! Serialization: JSON default; binary (bincode) optional when input does not start with '{'.

use std::net::SocketAddr;

use worldos_grpc::simulation_engine_server::SimulationEngineServer;
use worldos_grpc::transport_grpc::EngineService;
use worldos_grpc::transport_http;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    let grpc_addr_str = std::env::var("GRPC_ADDR").unwrap_or_else(|_| "0.0.0.0:50051".to_string());
    let http_addr_str = std::env::var("HTTP_ADDR").unwrap_or_else(|_| "0.0.0.0:50052".to_string());
    let grpc_addr: SocketAddr = grpc_addr_str.parse()?;
    let http_addr: SocketAddr = http_addr_str.parse()?;

    let svc = SimulationEngineServer::new(EngineService);
    let grpc_server = tonic::transport::Server::builder()
        .add_service(svc)
        .serve(grpc_addr);

    let http_app = transport_http::router();
    let http_server = axum::serve(
        tokio::net::TcpListener::bind(http_addr).await?,
        http_app,
    );

    println!(
        "WorldOS simulation engine: gRPC on {}, HTTP on {}",
        grpc_addr, http_addr
    );
    let _ = tokio::join!(grpc_server, http_server);
    Ok(())
}
