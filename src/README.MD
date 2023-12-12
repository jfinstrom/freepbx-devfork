# FreePBX Devfork

Fork and update FreePBX modules effortlessly with this command-line application. This will clone the repository, switch to the specified branch, and update the modules directory with a symlink.

## Disclaimer

This project is not affiliated with FreePBX nor my current employer. It is intended for educational purposes only.

## Table of Contents

-   [Installation](#installation)
-   [Usage](#usage)
-   [Configuration](#configuration)
-   [Composer Install](#composer-install)
-   [Contributing](#contributing)
-   [License](#license)

## Installation

To install the devfork tool, clone this repo and follow these steps:

```bash
composer install
```

## Usage

Use the following command to fork and update FreePBX modules:

```bash
php bin/devfork --help
```

### Options and Arguments

-   `-b, --branch=BRANCH`: The branch to switch to (default: "master").
-   `-u, --username[=USERNAME]`: Your GitHub username.
-   `-t, --token[=TOKEN]`: Your GitHub personal access token.
-   `--clone-path[=CLONE-PATH]`: The path for cloning repositories.
-   `--web-path[=WEB-PATH]`: The path for the web modules.
-   `-h, --help`: Display help for the command.

## Configuration

You can set parameters in `~/devfork.json`. Example:

```json
{
	"github": {
		"username": "your_username",
		"token": "your_personal_access_token"
	},
	"paths": {
		"clone": "/usr/src/",
		"web": "/var/www/html/admin/modules"
	}
}
```

## Composer Install

Don't forget to run `composer install` to install the required dependencies.

```bash
composer install
```

## Contributing

Contributions are welcome! Feel free to open issues and submit pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
