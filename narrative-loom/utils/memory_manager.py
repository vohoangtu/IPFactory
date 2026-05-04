import os
from typing import List, Dict, Any

from core.logging import get_logger

log = get_logger(__name__)

try:
    from langchain_community.vectorstores import Chroma
    HAS_CHROMA = True
except ImportError:
    HAS_CHROMA = False

class EpisodicMemoryManager:
    def __init__(self, db_dir: str = "./chroma_db", collection_name: str = "worldos_chronicles", api_key: str = None):
        self.enabled = HAS_CHROMA
        self.db_dir = db_dir
        if not self.enabled:
            log.warning(
                "memory_manager.disabled",
                reason="ChromaDB or LangChain not installed",
                hint="pip install chromadb langchain-community openai",
            )
            return

        # Dùng OpenAI Embeddings nếu có pool key, không thì dùng local HuggingFace
        if api_key and api_key != "not-needed":
            try:
                from langchain_openai import OpenAIEmbeddings
                self.embeddings = OpenAIEmbeddings(openai_api_key=api_key)
            except ImportError:
                self.enabled = False
                return
        else:
            try:
                from langchain_community.embeddings import HuggingFaceEmbeddings
                self.embeddings = HuggingFaceEmbeddings(model_name="keepitreal/vietnamese-sbert")
            except ImportError:
                log.warning(
                    "memory_manager.embeddings_missing",
                    hint="pip install sentence-transformers",
                )
                self.enabled = False
                return
                
        self.vector_store = Chroma(
            collection_name=collection_name, 
            embedding_function=self.embeddings,
            persist_directory=self.db_dir
        )
        
    def store_memory(self, prose: str, metadata: Dict[str, Any]):
        if not self.enabled: return
        # Nếu đoạn văn quá dài, thực tế ta có thể dùng CharacterTextSplitter, nhưng đây là prototype nhanh.
        self.vector_store.add_texts(texts=[prose], metadatas=[metadata])
        
    def retrieve_memories(self, query: str, k: int = 3, filter_metadata: Dict[str, Any] = None) -> List[str]:
        if not self.enabled: return []
        results = self.vector_store.similarity_search(query, k=k, filter=filter_metadata)
        return [doc.page_content for doc in results]
