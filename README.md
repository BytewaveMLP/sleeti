# sleeti

[![standard-readme compliant](https://img.shields.io/badge/readme%20style-standard-brightgreen.svg?style=flat-square)](https://github.com/RichardLitt/standard-readme)

> Simple, free, open source file sharing

Welcome to sleeti, a summer project turned full-time learning experience. sleeti is my personal adventure into PHP MVC app design, and tries to incorporate various security and design "best-practices" into a fully-featured file sharing application. sleeti's development began in August 2016, sparked by [Codecourse](https://www.youtube.com/user/phpacademy)'s [Authentication with Slim 3](https://www.youtube.com/watch?v=RhcQXFeor9g&list=PLfdtiltiRHWGc_yY90XRdq6mRww042aEC) series, and has since evolved into a self-teaching tool that I've used to hack at and home my skills.

## Table of Contents

- [Background](#background)
- [Install](#install)
    - [Prerequisites](#prerequisites)
	- [Installation](#installation)
- [Usage](#usage)
- [Maintainers](#maintainers)
- [Contribute](#contribute)
- [License](#license)

## Background

Covered in-depth [here](https://bytewave.antigravities.net/blog/2017/06/02/looking-back-sleeti/), but you can find a summary below:

I started sleeti with the intent to clone [eeti.me](https://eeti.me), an invite-only file sharing project by a friend of mine. It originated almost out of a joke, as I wasn't a fan of procedural PHP (which eeti was written in) and eeti's author wasn't a fan of MVC. sleeti started in the summer of 2016 (around August according to my `git` history), and since then it's grown into a fun learning tool for security and app design.

## Install

### Prerequisites

- Apache/nginx
- PHP >= 7.0
- MySQL >= 5.5.3
- CLI access + Composer

### Installation

```
$ git clone https://github.com/BytewaveMLP/sleeti
$ cd sleeti
$ composer install
$ mysql -u <your MySQL user> -p
> CREATE DATABASE sleeti;
> SOURCE ./sleeti.sql;
> EXIT;
```

At this point, install your `nginx` configs if necessary and restart your webserver. Then, just browse to  `http://yourdomain.ext/install` and fill out the form presented.

## Usage

After setting up sleeti, you should be warned that the first account registered will be an administrative account. Go ahead and register an account at `/auth/signup`. As stated, your account will have full administrative access, and will be able to manage everything about the site.

From there? Explore! Go public if you want! The sky's the limit!*

\* - **Note:** The sky is not actually the limit.

## Maintainers

- [BytewaveMLP](https://github.com/BytewaveMLP)

## Contribute

**Issues, suggestions, or concerns?** Submit a GitHub issue!

**Want to add a feature?** We accept PRs!

All upcoming and completed features, bugfixes, etc are listed [on the Trello board](https://trello.com/b/e5rzo48n/sleeti)

## License

Copyright (c) Eliot Partridge, 2016-17. Licensed under [the MPL v2.0](/LICENSE).
