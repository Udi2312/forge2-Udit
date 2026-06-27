#!/usr/bin/env python3
"""Simple CI runner that watches the #ci-cd channel and runs tests.

Behavior:
- Polls `#ci-cd` for messages containing "Running tests".
- Runs `pytest -q` (falls back to `python -m unittest discover`).
- Posts results back to `#ci-cd` and, on success, creates a PR using `/.github/create_pr.py`.

Notes:
- Requires `GITHUB_TOKEN` in the environment to create the PR. Do NOT paste the token into chat.
"""
import os
import time
import subprocess
from agents import messaging


def get_repo_owner_repo():
    try:
        out = subprocess.check_output(['git', 'config', '--get', 'remote.origin.url'], text=True).strip()
        if out.startswith('git@'):
            # git@github.com:owner/repo.git
            path = out.split(':', 1)[1]
        else:
            # https://github.com/owner/repo.git
            parts = out.split('/')
            path = '/'.join(parts[-2:])
        owner, repo = path.replace('.git', '').split('/')
        return owner, repo
    except Exception as e:
        print('Could not determine repo owner/repo:', e)
        return None, None


def run_tests():
    # try pytest first
    try:
        subprocess.check_call(['pytest', '-q'])
        return True, 'pytest passed'
    except Exception:
        try:
            subprocess.check_call(['python', '-m', 'unittest', 'discover'])
            return True, 'unittest passed'
        except Exception as e:
            return False, str(e)


def create_pr_if_missing(owner, repo, head, base='main', title=None, body=''):
    title = title or f'Auto PR: {head}'
    token = os.environ.get('GITHUB_TOKEN')
    if not token:
        return False, 'GITHUB_TOKEN not set'
    cmd = ['python', '.github/create_pr.py', '--owner', owner, '--repo', repo, '--head', head, '--base', base, '--title', title, '--body', body]
    env = os.environ.copy()
    proc = subprocess.run(cmd, env=env, capture_output=True, text=True)
    if proc.returncode == 0:
        return True, proc.stdout.strip()
    return False, proc.stderr.strip() or proc.stdout.strip()


def watch_ci_and_run(poll_interval: float = 2.0, branch: str = None):
    owner, repo = get_repo_owner_repo()
    if not owner:
        messaging.publish('#ci-cd', 'ci-runner', 'Could not determine repository; aborting CI runner.')
        return
    offset = messaging.get_offset('#ci-cd')
    while True:
        msgs, offset = messaging.read_since('#ci-cd', offset)
        for m in msgs:
            text = m.get('text','').lower()
            if 'running tests' in text or 'run tests' in text:
                messaging.publish('#ci-cd', 'ci-runner', 'CI: Starting tests...')
                ok, details = run_tests()
                if ok:
                    messaging.publish('#ci-cd', 'ci-runner', f'CI: Tests passed ({details})')
                    head = branch or subprocess.check_output(['git','rev-parse','--abbrev-ref','HEAD'], text=True).strip()
                    ok2, out = create_pr_if_missing(owner, repo, head, title=f'Auto PR from CI: {head}', body='Automated PR created by CI runner')
                    if ok2:
                        messaging.publish('#human-review', 'ci-runner', f'PR created: {out}')
                    else:
                        messaging.publish('#ci-cd', 'ci-runner', f'CI: PR creation failed: {out}')
                else:
                    messaging.publish('#ci-cd', 'ci-runner', f'CI: Tests failed: {details}')
        time.sleep(poll_interval)


if __name__ == '__main__':
    import argparse
    p = argparse.ArgumentParser()
    p.add_argument('--loop', action='store_true', help='Keep running and polling')
    p.add_argument('--branch', help='Head branch to create PR from (defaults to current)')
    args = p.parse_args()
    if args.loop:
        watch_ci_and_run(branch=args.branch)
    else:
        # run one pass: check for any recent "Running tests" and handle them
        offset = messaging.get_offset('#ci-cd')
        msgs, _ = messaging.read_since('#ci-cd', max(0, offset-50))
        triggered = any('running tests' in (m.get('text','').lower()) for m in msgs)
        if triggered:
            watch_ci_and_run(poll_interval=0.5, branch=args.branch)
        else:
            print('No CI triggers found in #ci-cd')
