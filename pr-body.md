## PulseDesk Agent Orchestration and Documentation

### What changed
- Added local Hermes/OpenClaw channel orchestration for task assignment and result reporting
- Implemented CI runner workflow for `#ci-cd` channel and PR creation helper
- Added Slack token persistence support in `agents/openclaw/.env`
- Documented repository structure, architecture, sprint progress, and agent loop
- Updated root README, submission checklist, sprint docs, and agent log

### Why
This PR brings the current repo state into alignment with the requested Slack workflow, GitHub integration, and documentation completion.

### Notes
- Slack integration requires `SLACK_BOT_TOKEN` in `agents/openclaw/.env`
- GitHub PR automation requires `GITHUB_TOKEN` in the environment
