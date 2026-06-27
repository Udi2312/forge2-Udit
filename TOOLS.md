# TOOLS.md - Local Notes

## Local setup
- Workspace root: `D:\forge2-UditBansal`
- GitHub repo: `https://github.com/Udi2312/forge2-Udit.git`
- Git remote origin is configured for this repository

## Agent tooling
- `openclaw` CLI installed, version `2026.6.10`
- OpenClaw config: `agents/openclaw/openclaw.json`
- Local OpenClaw secret token file: `agents/openclaw/.env`
- Slack bot token variable: `SLACK_BOT_TOKEN`
- Slack app token variable: `SLACK_APP_TOKEN`

## CI / GitHub
- GitHub PR helper script: `.github/create_pr.py`
- CI runner script: `agents/ci/ci_runner.py`
- GitHub token source: environment variable `GITHUB_TOKEN`

## Notes
- Keep `agents/openclaw/.env` out of git; `.gitignore` already ignores secrets
- `agents/channels/` stores local message bus logs by channel
