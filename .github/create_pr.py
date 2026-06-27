#!/usr/bin/env python3
"""Create a GitHub PR using GITHUB_TOKEN from environment.

Usage: python .github/create_pr.py --owner OWNER --repo REPO --head BRANCH --base BASE --title TITLE --body BODY
"""
import os
import sys
import json
import argparse
from urllib import request, parse, error


def create_pr(token, owner, repo, head, base, title, body):
    url = f"https://api.github.com/repos/{owner}/{repo}/pulls"
    payload = {"title": title, "head": head, "base": base, "body": body}
    data = json.dumps(payload).encode('utf-8')
    req = request.Request(url, data=data, method='POST')
    req.add_header('Accept', 'application/vnd.github+json')
    req.add_header('Authorization', f'token {token}')
    req.add_header('User-Agent', 'forge2-agent')
    try:
        with request.urlopen(req) as resp:
            out = json.load(resp)
            print('PR created:', out.get('html_url'))
            return 0
    except error.HTTPError as e:
        try:
            err = e.read().decode('utf-8')
            print('HTTPError', e.code, err)
        except Exception:
            print('HTTPError', e)
        return 2
    except Exception as e:
        print('Error creating PR:', e)
        return 3


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--owner', required=True)
    p.add_argument('--repo', required=True)
    p.add_argument('--head', required=True)
    p.add_argument('--base', default='main')
    p.add_argument('--title', required=True)
    p.add_argument('--body', default='')
    args = p.parse_args()
    token = os.environ.get('GITHUB_TOKEN')
    if not token:
        print('GITHUB_TOKEN not set in environment. Export a PAT with repo scope and retry.')
        return 1
    return create_pr(token, args.owner, args.repo, args.head, args.base, args.title, args.body)


if __name__ == '__main__':
    sys.exit(main())
