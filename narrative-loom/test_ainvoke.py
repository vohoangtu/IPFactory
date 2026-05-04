import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
import asyncio
from langchain_openai import ChatOpenAI
from langchain_core.messages import HumanMessage

async def main():
    llm = ChatOpenAI(
        model_name='qwen/qwen3.5-9b', 
        base_url='http://host.docker.internal:1234/v1', 
        api_key='lm-studio'
    )
    res = await llm.ainvoke([HumanMessage(content='Hello')])
    print(res.content)

if __name__ == '__main__':
    asyncio.run(main())
