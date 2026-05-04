//! Kafka Event Streaming via Redpanda REST Proxy
use serde::Serialize;
use std::sync::OnceLock;

static CLIENT: OnceLock<reqwest::Client> = OnceLock::new();

#[derive(Serialize)]
struct KafkaRecord<'a, T: Serialize> {
    key: Option<&'a str>,
    value: &'a T,
}

#[derive(Serialize)]
struct KafkaBatchRequest<'a, T: Serialize> {
    records: Vec<KafkaRecord<'a, T>>,
}

pub async fn send_state_update<T: Serialize>(topic: &str, key: &str, payload: &T) -> Result<(), String> {
    let client = CLIENT.get_or_init(reqwest::Client::new);
    let url = format!("http://redpanda:8082/topics/{}", topic);

    let body = KafkaBatchRequest {
        records: vec![KafkaRecord {
            key: Some(key),
            value: payload,
        }],
    };

    match client.post(&url)
        .header("Content-Type", "application/vnd.kafka.json.v2+json")
        .json(&body)
        .send()
        .await 
    {
        Ok(res) if res.status().is_success() => Ok(()),
        Ok(res) => Err(format!("Kafka REST Proxy error: {}", res.status())),
        Err(e) => Err(format!("Kafka connection failed: {}", e)),
    }
}
