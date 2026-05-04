"""
SwarmProfileFactory: Generate agent profiles from WorldContext
without requiring Zep or external LLM dependencies.

Produces lightweight profiles that SimulationRunner can consume.
"""

import random
from dataclasses import dataclass, field
from typing import List, Dict, Any


@dataclass
class SwarmAgentProfile:
    """Agent profile for OASIS simulation."""
    user_id: int
    user_name: str
    name: str
    bio: str
    persona: str
    age: int = 25
    gender: str = "unknown"
    profession: str = "commoner"
    interested_topics: List[str] = field(default_factory=list)

    def to_dict(self) -> Dict[str, Any]:
        return {
            "user_id": self.user_id,
            "user_name": self.user_name,
            "name": self.name,
            "bio": self.bio,
            "persona": self.persona,
            "age": self.age,
            "gender": self.gender,
            "profession": self.profession,
            "interested_topics": self.interested_topics,
        }

    def to_twitter_format(self) -> Dict[str, Any]:
        return {
            **self.to_dict(),
            "friend_count": random.randint(5, 50),
            "follower_count": random.randint(10, 200),
            "statuses_count": random.randint(0, 100),
        }

    def to_reddit_format(self) -> Dict[str, Any]:
        return {
            **self.to_dict(),
            "karma": random.randint(10, 500),
        }


# Archetype templates per social structure
_ARCHETYPE_POOL = {
    "default": [
        {"profession": "merchant", "topics": ["trade", "economy"], "bio_template": "A {era} merchant dealing in goods and information"},
        {"profession": "soldier", "topics": ["military", "honor"], "bio_template": "A {era} warrior sworn to defend the realm"},
        {"profession": "scholar", "topics": ["knowledge", "philosophy"], "bio_template": "A {era} scholar seeking truth and wisdom"},
        {"profession": "farmer", "topics": ["agriculture", "weather"], "bio_template": "A {era} farmer working the land"},
        {"profession": "priest", "topics": ["religion", "morality"], "bio_template": "A {era} priest guiding the faithful"},
        {"profession": "artisan", "topics": ["crafts", "art"], "bio_template": "A {era} artisan creating works of beauty"},
        {"profession": "noble", "topics": ["politics", "power"], "bio_template": "A {era} noble navigating court intrigue"},
        {"profession": "thief", "topics": ["crime", "survival"], "bio_template": "A {era} rogue surviving in the shadows"},
        {"profession": "healer", "topics": ["medicine", "herbs"], "bio_template": "A {era} healer tending the sick and wounded"},
        {"profession": "bard", "topics": ["stories", "music"], "bio_template": "A {era} bard spreading tales across the land"},
    ]
}

# Name pools (generic fantasy-friendly)
_FIRST_NAMES = [
    "Aldric", "Brenna", "Cassian", "Dahlia", "Edric", "Freya", "Galen", "Helena",
    "Isran", "Jora", "Kael", "Lyra", "Magnus", "Nadia", "Orin", "Petra",
    "Quinn", "Rhea", "Soren", "Thea", "Ulric", "Vera", "Wren", "Xara",
    "Yoren", "Zara", "Amos", "Bela", "Corwin", "Dara",
]

_LAST_NAMES = [
    "Ashford", "Blackwood", "Crestfall", "Duskmore", "Everhart", "Foxglove",
    "Grimshaw", "Holloway", "Ironforge", "Jade", "Kingsley", "Loreweaver",
    "Moonvale", "Nightshade", "Oakenshield", "Pinefall", "Quillwright",
    "Ravenscroft", "Stormwind", "Thornfield",
]


class SwarmProfileFactory:
    """Generate agent profiles from WorldContext."""

    def generate_profiles(self, context) -> List[SwarmAgentProfile]:
        """
        Generate N agent profiles based on WorldContext.

        Args:
            context: WorldContext with era, social_structure, event_trigger, agents_count

        Returns:
            List of SwarmAgentProfile instances
        """
        count = min(context.agents_count, 50)  # Cap at 50
        archetypes = _ARCHETYPE_POOL.get("default", [])

        rng = random.Random()
        profiles = []

        first_names = list(_FIRST_NAMES)
        last_names = list(_LAST_NAMES)
        rng.shuffle(first_names)
        rng.shuffle(last_names)

        for i in range(count):
            archetype = archetypes[i % len(archetypes)]

            first = first_names[i % len(first_names)]
            last = last_names[i % len(last_names)]
            name = f"{first} {last}"
            user_name = f"{first.lower()}_{last.lower()}"

            bio = archetype["bio_template"].format(era=context.era)
            persona = (
                f"You are {name}, a {archetype['profession']} in a world of {context.era}. "
                f"Technology: {context.tech_level}. Society: {context.social_structure}. "
                f"Communication: {context.communication_method}. "
                f"Recent event: {context.event_trigger}. "
                f"React and behave as your character would."
            )

            profile = SwarmAgentProfile(
                user_id=i + 1,
                user_name=user_name,
                name=name,
                bio=bio,
                persona=persona,
                age=rng.randint(18, 65),
                gender=rng.choice(["male", "female"]),
                profession=archetype["profession"],
                interested_topics=archetype["topics"] + [context.era.lower().split()[0]],
            )
            profiles.append(profile)

        return profiles
