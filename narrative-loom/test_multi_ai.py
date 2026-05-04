import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
from openai import OpenAI

client = OpenAI(
    base_url="http://host.docker.internal:8045/v1",
    api_key="sk-7c88b247834a4b1bb73051b131be2c32"
)

try:
    response = client.chat.completions.create(
        model="gemini-3-flash",
        messages=[{"role": "user", "content": "Hello"}]
    )
    print(response.choices[0].message.content)
except Exception as e:
    print(f"Error: {e}")
