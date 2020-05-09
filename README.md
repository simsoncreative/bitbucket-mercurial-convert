# Bitbucket Mercurial to Git converter

This is a super simple and quick hack using the Bitbucket API and [hg-export-tool](https://github.com/chrisjbillington/hg-export-tool) to get all your Mercurial repositories and essentially copy them to a new git repository.

## Setup

```bash
git clone git@github.com:simsoncreative/bitbucket-mercurial-convert.git
cd bitbucket-mercurial-convert
git submodule init
pip install mercurial
```

Create a config file
```bash
cp config.json.example config.json
```

## Running the script

```bash
php convert.php
```
