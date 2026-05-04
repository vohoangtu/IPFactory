"""
进程生命周期管理器
负责模拟进程的启动、终止、监控和资源清理
"""

import os
import sys
import subprocess
import signal
import atexit
from typing import Dict, Any, List, Optional

from ..utils.logger import get_logger

logger = get_logger('mirofish.process_manager')

IS_WINDOWS = sys.platform == 'win32'
_cleanup_registered = False


class ProcessManager:
    """
    进程管理器
    
    负责：
    1. 启动模拟子进程
    2. 跨平台终止进程及其子进程
    3. 管理 stdout/stderr 文件句柄
    4. 服务器关闭时的统一清理
    """
    
    _processes: Dict[str, subprocess.Popen] = {}
    _stdout_files: Dict[str, Any] = {}
    _stderr_files: Dict[str, Any] = {}
    _cleanup_done = False
    
    @classmethod
    def spawn(
        cls,
        simulation_id: str,
        cmd: List[str],
        cwd: str,
        env: Dict[str, str],
        stdout_path: str,
    ) -> subprocess.Popen:
        """
        启动模拟子进程
        
        Args:
            simulation_id: 模拟ID
            cmd: 命令列表
            cwd: 工作目录
            env: 环境变量字典
            stdout_path: stdout/stderr 日志文件路径
            
        Returns:
            subprocess.Popen 对象
        """
        main_log_file = open(stdout_path, 'w', encoding='utf-8')
        
        process = subprocess.Popen(
            cmd,
            cwd=cwd,
            stdout=main_log_file,
            stderr=subprocess.STDOUT,
            text=True,
            encoding='utf-8',
            bufsize=1,
            env=env,
            start_new_session=True,
        )
        
        cls._processes[simulation_id] = process
        cls._stdout_files[simulation_id] = main_log_file
        cls._stderr_files[simulation_id] = None
        
        logger.info(f"进程启动: simulation_id={simulation_id}, pid={process.pid}")
        return process
    
    @classmethod
    def get_process(cls, simulation_id: str) -> Optional[subprocess.Popen]:
        """获取模拟进程"""
        return cls._processes.get(simulation_id)
    
    @classmethod
    def is_running(cls, simulation_id: str) -> bool:
        """检查模拟进程是否仍在运行"""
        process = cls._processes.get(simulation_id)
        return process is not None and process.poll() is None
    
    @classmethod
    def terminate(cls, simulation_id: str, timeout: int = 10):
        """
        终止指定模拟的进程
        
        Args:
            simulation_id: 模拟ID
            timeout: 等待进程退出的超时时间（秒）
        """
        process = cls._processes.get(simulation_id)
        if not process or process.poll() is not None:
            return
        
        cls._terminate_process_impl(process, simulation_id, timeout)
    
    @classmethod
    def _terminate_process_impl(
        cls,
        process: subprocess.Popen,
        simulation_id: str,
        timeout: int = 10
    ):
        """
        跨平台终止进程及其子进程
        """
        if IS_WINDOWS:
            logger.info(f"终止进程树 (Windows): simulation={simulation_id}, pid={process.pid}")
            try:
                subprocess.run(
                    ['taskkill', '/PID', str(process.pid), '/T'],
                    capture_output=True,
                    timeout=5
                )
                try:
                    process.wait(timeout=timeout)
                except subprocess.TimeoutExpired:
                    logger.warning(f"进程未响应，强制终止: {simulation_id}")
                    subprocess.run(
                        ['taskkill', '/F', '/PID', str(process.pid), '/T'],
                        capture_output=True,
                        timeout=5
                    )
                    process.wait(timeout=5)
            except Exception as e:
                logger.warning(f"taskkill 失败，尝试 terminate: {e}")
                process.terminate()
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
        else:
            pgid = os.getpgid(process.pid)
            logger.info(f"终止进程组 (Unix): simulation={simulation_id}, pgid={pgid}")
            
            os.killpg(pgid, signal.SIGTERM)
            
            try:
                process.wait(timeout=timeout)
            except subprocess.TimeoutExpired:
                logger.warning(f"进程组未响应 SIGTERM，强制终止: {simulation_id}")
                os.killpg(pgid, signal.SIGKILL)
                process.wait(timeout=5)
    
    @classmethod
    def cleanup_simulation(cls, simulation_id: str):
        """
        清理单个模拟的进程资源（关闭文件句柄，从字典中移除）
        """
        cls._processes.pop(simulation_id, None)
        
        if simulation_id in cls._stdout_files:
            try:
                cls._stdout_files[simulation_id].close()
            except Exception:
                pass
            cls._stdout_files.pop(simulation_id, None)
        
        if simulation_id in cls._stderr_files and cls._stderr_files[simulation_id]:
            try:
                cls._stderr_files[simulation_id].close()
            except Exception:
                pass
            cls._stderr_files.pop(simulation_id, None)
    
    @classmethod
    def get_running_simulations(cls) -> List[str]:
        """获取所有正在运行的模拟ID列表"""
        running = []
        for sim_id, process in cls._processes.items():
            if process.poll() is None:
                running.append(sim_id)
        return running
    
    @classmethod
    def cleanup_all(cls):
        """
        清理所有模拟进程和文件句柄
        
        在服务器关闭时调用，确保所有子进程被终止
        """
        if cls._cleanup_done:
            return
        cls._cleanup_done = True
        
        if not cls._processes:
            return
        
        logger.info("正在清理所有模拟进程...")
        
        processes = list(cls._processes.items())
        for simulation_id, process in processes:
            try:
                if process.poll() is None:
                    logger.info(f"终止模拟进程: {simulation_id}, pid={process.pid}")
                    try:
                        cls._terminate_process_impl(process, simulation_id, timeout=5)
                    except (ProcessLookupError, OSError):
                        try:
                            process.terminate()
                            process.wait(timeout=3)
                        except Exception:
                            process.kill()
            except Exception as e:
                logger.error(f"清理进程失败: {simulation_id}, error={e}")
        
        # 关闭文件句柄
        for simulation_id, file_handle in list(cls._stdout_files.items()):
            try:
                if file_handle:
                    file_handle.close()
            except Exception:
                pass
        cls._stdout_files.clear()
        
        for simulation_id, file_handle in list(cls._stderr_files.items()):
            try:
                if file_handle:
                    file_handle.close()
            except Exception:
                pass
        cls._stderr_files.clear()
        
        cls._processes.clear()
        logger.info("模拟进程清理完成")
