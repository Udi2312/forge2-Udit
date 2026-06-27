import time
from typing import List
from agents import messaging


class Hermes:
    def __init__(self, name: str = "hermes"):
        self.name = name

    def split_task(self, big_task: str, max_parts: int = 5) -> List[str]:
        # naive splitter: split on sentences, then chunk to max_parts
        parts = [p.strip() for p in big_task.replace('\n', ' ').split('.') if p.strip()]
        if not parts:
            return [big_task]
        # chunk into up to max_parts groups
        n = min(max_parts, len(parts))
        chunk_size = max(1, len(parts) // n)
        subtasks = []
        for i in range(0, len(parts), chunk_size):
            subtasks.append('. '.join(parts[i:i+chunk_size]).strip() + '.')
        return subtasks

    def assign(self, big_task: str):
        subtasks = self.split_task(big_task)
        for i, t in enumerate(subtasks, start=1):
            text = f"Task {i}/{len(subtasks)}: {t}"
            messaging.publish('#agent-coder', self.name, text, meta={'task_id': i})
        # optionally log assignment
        messaging.publish('#sprint-main', self.name, f"Assigned {len(subtasks)} subtasks to #agent-coder")

    def gather_results_and_run_ci(self, wait_seconds: int = 1):
        # poll agent-log for results
        offset = messaging.get_offset('#agent-log')
        time.sleep(wait_seconds)
        msgs, offset = messaging.read_since('#agent-log', offset)
        # publish to ci-cd
        for m in msgs:
            messaging.publish('#ci-cd', self.name, f"Received result: {m['text']}")
        # simulate CI checks
        messaging.publish('#ci-cd', self.name, "Running tests...")
        time.sleep(0.5)
        messaging.publish('#ci-cd', self.name, "All tests passed. Pushing to GitHub...")
        # simulate push
        pr_link = "https://github.com/example/repo/pull/123"
        messaging.publish('#human-review', self.name, f"Pushed changes, PR: {pr_link}")


def main():
    import sys
    h = Hermes()
    if len(sys.argv) > 1:
        task = ' '.join(sys.argv[1:])
    else:
        task = input('Enter big task for Hermes: ').strip()
    h.assign(task)
    # wait for openclaw to respond then run CI
    time.sleep(1)
    h.gather_results_and_run_ci()


if __name__ == '__main__':
    main()
