import time
from agents import messaging


class OpenClaw:
    def __init__(self, name: str = 'openclaw'):
        self.name = name

    def process_task(self, text: str, meta: dict):
        # simple processing simulation: echo with a success marker
        result = f"Processed ({meta.get('task_id', '?')}): {text} -- result: OK"
        # simulate running unit tests for the task
        test_result = {'status': 'passed', 'details': 'All assertions OK'}
        messaging.publish('#agent-log', self.name, result, meta={'test': test_result})

    def poll_and_work(self, poll_interval: float = 0.5, once: bool = True):
        offset = messaging.get_offset('#agent-coder')
        while True:
            msgs, offset = messaging.read_since('#agent-coder', offset)
            for m in msgs:
                self.process_task(m['text'], m.get('meta', {}))
            if once:
                break
            time.sleep(poll_interval)


def main():
    import sys
    o = OpenClaw()
    # run once by default; use 'loop' arg to keep listening
    once = True
    if len(sys.argv) > 1 and sys.argv[1] == 'loop':
        once = False
    o.poll_and_work(once=once)


if __name__ == '__main__':
    main()
