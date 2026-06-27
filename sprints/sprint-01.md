# Sprint 01 -- Local Agent Orchestration

Goal: Build the initial Hermes / OpenClaw task orchestration pattern and wire Slack-style channel logs into the repo.

Models: Hermes, OpenClaw

## Issues
- [x] Create `agents/messaging.py` for file-backed channel logs
- [x] Create `agents/hermes/hermes.py` to split tasks and publish to `#agent-coder`
- [x] Create `agents/openclaw/openclaw.py` to consume `#agent-coder` and publish to `#agent-log`
- [x] Create CI runner workflow and GitHub PR helper script
- [x] Add `.gitignore` rules to protect local secrets

## Outcome
- Shipped: local task orchestration prototype, channel logs, PR helper, CI runner, Slack config design
- Slipped / moved to next sprint: real Slack / GitHub API verification and documentation completion
- PRs: prototype work in local branch, ready for review
