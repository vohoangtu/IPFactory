"""
JSONL 动作日志解析器
负责读取和解析模拟产生的动作日志文件
"""

import os
import json
from typing import Dict, Any, List, Optional

from ..utils.logger import get_logger
from .runner_models import AgentAction, SimulationRunState

logger = get_logger('mirofish.log_parser')


class LogParser:
    """
    日志解析器
    
    负责：
    1. 逐行读取 JSONL 动作日志
    2. 解析为 AgentAction 对象
    3. 处理事件类型条目（round_end, simulation_end 等）
    4. 支持过滤和分页查询
    """
    
    @staticmethod
    def read_action_log(
        log_path: str,
        position: int,
        state: SimulationRunState,
        platform: str,
        graph_updater=None
    ) -> int:
        """
        读取动作日志文件
        
        Args:
            log_path: 日志文件路径
            position: 上次读取位置
            state: 运行状态对象
            platform: 平台名称 (twitter/reddit)
            graph_updater: 图谱记忆更新器（可选）
            
        Returns:
            新的读取位置
        """
        try:
            with open(log_path, 'r', encoding='utf-8') as f:
                f.seek(position)
                for line in f:
                    line = line.strip()
                    if line:
                        try:
                            action_data = json.loads(line)
                            
                            # 处理事件类型的条目
                            if "event_type" in action_data:
                                event_type = action_data.get("event_type")
                                
                                # 检测 simulation_end 事件
                                if event_type == "simulation_end":
                                    if platform == "twitter":
                                        state.twitter_completed = True
                                        state.twitter_running = False
                                        logger.info(
                                            f"Twitter 模拟已完成: {state.simulation_id}, "
                                            f"total_rounds={action_data.get('total_rounds')}, "
                                            f"total_actions={action_data.get('total_actions')}"
                                        )
                                    elif platform == "reddit":
                                        state.reddit_completed = True
                                        state.reddit_running = False
                                        logger.info(
                                            f"Reddit 模拟已完成: {state.simulation_id}, "
                                            f"total_rounds={action_data.get('total_rounds')}, "
                                            f"total_actions={action_data.get('total_actions')}"
                                        )
                                    continue
                                
                                # 更新轮次信息（从 round_end 事件）
                                elif event_type == "round_end":
                                    round_num = action_data.get("round", 0)
                                    simulated_hours = action_data.get("simulated_hours", 0)
                                    
                                    if platform == "twitter":
                                        if round_num > state.twitter_current_round:
                                            state.twitter_current_round = round_num
                                        state.twitter_simulated_hours = simulated_hours
                                    elif platform == "reddit":
                                        if round_num > state.reddit_current_round:
                                            state.reddit_current_round = round_num
                                        state.reddit_simulated_hours = simulated_hours
                                    
                                    if round_num > state.current_round:
                                        state.current_round = round_num
                                    state.simulated_hours = max(
                                        state.twitter_simulated_hours,
                                        state.reddit_simulated_hours
                                    )
                                    continue
                            
                            action = AgentAction(
                                round_num=action_data.get("round", 0),
                                timestamp=action_data.get("timestamp", ""),
                                platform=platform,
                                agent_id=action_data.get("agent_id", 0),
                                agent_name=action_data.get("agent_name", ""),
                                action_type=action_data.get("action_type", ""),
                                action_args=action_data.get("action_args", {}),
                                result=action_data.get("result"),
                                success=action_data.get("success", True),
                            )
                            state.add_action(action)
                            
                            if action.round_num and action.round_num > state.current_round:
                                state.current_round = action.round_num
                            
                            if graph_updater:
                                graph_updater.add_activity_from_dict(action_data, platform)
                            
                        except json.JSONDecodeError:
                            pass
                return f.tell()
        except Exception as e:
            logger.warning(f"读取动作日志失败: {log_path}, error={e}")
            return position
    
    @staticmethod
    def read_actions_from_file(
        file_path: str,
        default_platform: Optional[str] = None,
        platform_filter: Optional[str] = None,
        agent_id: Optional[int] = None,
        round_num: Optional[int] = None
    ) -> List[AgentAction]:
        """
        从单个动作文件中读取动作
        
        Args:
            file_path: 动作日志文件路径
            default_platform: 默认平台
            platform_filter: 过滤平台
            agent_id: 过滤 Agent ID
            round_num: 过滤轮次
            
        Returns:
            AgentAction 列表
        """
        if not os.path.exists(file_path):
            return []
        
        actions = []
        
        with open(file_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line:
                    continue
                
                try:
                    data = json.loads(line)
                    
                    # 跳过非动作记录
                    if "event_type" in data:
                        continue
                    
                    if "agent_id" not in data:
                        continue
                    
                    record_platform = data.get("platform") or default_platform or ""
                    
                    if platform_filter and record_platform != platform_filter:
                        continue
                    if agent_id is not None and data.get("agent_id") != agent_id:
                        continue
                    if round_num is not None and data.get("round") != round_num:
                        continue
                    
                    actions.append(AgentAction(
                        round_num=data.get("round", 0),
                        timestamp=data.get("timestamp", ""),
                        platform=record_platform,
                        agent_id=data.get("agent_id", 0),
                        agent_name=data.get("agent_name", ""),
                        action_type=data.get("action_type", ""),
                        action_args=data.get("action_args", {}),
                        result=data.get("result"),
                        success=data.get("success", True),
                    ))
                    
                except json.JSONDecodeError:
                    continue
        
        return actions
    
    @classmethod
    def get_all_actions(
        cls,
        simulation_id: str,
        run_state_dir: str,
        platform: Optional[str] = None,
        agent_id: Optional[int] = None,
        round_num: Optional[int] = None
    ) -> List[AgentAction]:
        """
        获取所有平台的完整动作历史（无分页限制）
        
        Returns:
            完整的动作列表（按时间戳排序，新的在前）
        """
        sim_dir = os.path.join(run_state_dir, simulation_id)
        actions = []
        
        twitter_actions_log = os.path.join(sim_dir, "twitter", "actions.jsonl")
        if not platform or platform == "twitter":
            actions.extend(cls.read_actions_from_file(
                twitter_actions_log,
                default_platform="twitter",
                platform_filter=platform,
                agent_id=agent_id,
                round_num=round_num
            ))
        
        reddit_actions_log = os.path.join(sim_dir, "reddit", "actions.jsonl")
        if not platform or platform == "reddit":
            actions.extend(cls.read_actions_from_file(
                reddit_actions_log,
                default_platform="reddit",
                platform_filter=platform,
                agent_id=agent_id,
                round_num=round_num
            ))
        
        # 回退到旧格式
        if not actions:
            actions_log = os.path.join(sim_dir, "actions.jsonl")
            actions = cls.read_actions_from_file(
                actions_log,
                default_platform=None,
                platform_filter=platform,
                agent_id=agent_id,
                round_num=round_num
            )
        
        actions.sort(key=lambda x: x.timestamp, reverse=True)
        return actions
    
    @classmethod
    def get_actions(
        cls,
        simulation_id: str,
        run_state_dir: str,
        limit: int = 100,
        offset: int = 0,
        platform: Optional[str] = None,
        agent_id: Optional[int] = None,
        round_num: Optional[int] = None
    ) -> List[AgentAction]:
        """
        获取动作历史（带分页）
        """
        actions = cls.get_all_actions(
            simulation_id=simulation_id,
            run_state_dir=run_state_dir,
            platform=platform,
            agent_id=agent_id,
            round_num=round_num
        )
        return actions[offset:offset + limit]
