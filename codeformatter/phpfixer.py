import os
import shutil


FIXERS = ('pint', 'php-cs-fixer', 'phpcbf')

# Within one directory this order breaks ties between fixers.
CONFIG_FILES = [
    ('pint', ['pint.json']),
    ('php-cs-fixer', ['.php-cs-fixer.php', '.php-cs-fixer.dist.php']),
    ('phpcbf', ['phpcs.xml', '.phpcs.xml', 'phpcs.xml.dist',
                '.phpcs.xml.dist']),
]

PACKAGES = {
    'pint': 'laravel/pint',
    'php-cs-fixer': 'friendsofphp/php-cs-fixer',
    'phpcbf': 'squizlabs/php_codesniffer',
}


def _upward(directory):
    """Yield directory and each of its parents up to the filesystem root."""
    while True:
        yield directory
        parent = os.path.dirname(directory)
        if parent == directory:
            return
        directory = parent


def _config_in(directory, only=None):
    """Return (fixer, config_file) for the first configuration file found
    in directory, considering only the fixer named by only when given."""
    for fixer, names in CONFIG_FILES:
        if only and fixer != only:
            continue
        for name in names:
            path = os.path.join(directory, name)
            if os.path.isfile(path):
                return fixer, path
    return None


def _nearest_composer_dir(directory):
    for d in _upward(directory):
        if os.path.isfile(os.path.join(d, 'composer.json')):
            return d
    return None


def find_config(file_name, forced):
    """Pick the fixer for file_name.

    Returns (fixer, config_dir, config_file) or None when no fixer is
    configured. config_file is None when the fixer runs with its defaults;
    config_dir is None only for a never-saved buffer with a forced fixer.
    """
    if not file_name:
        return (forced, None, None) if forced else None

    file_dir = os.path.dirname(file_name)

    for d in _upward(file_dir):
        found = _config_in(d, forced)
        if found:
            return found[0], d, found[1]

    composer_dir = _nearest_composer_dir(file_dir)
    if forced:
        return forced, composer_dir or file_dir, None

    # Without a configuration file, a Composer project with Pint installed
    # formats with Pint's default preset, as ./vendor/bin/pint does.
    if composer_dir and os.path.isfile(
            os.path.join(composer_dir, 'vendor', 'bin', 'pint')):
        return 'pint', composer_dir, None
    return None


def find_executable(fixer, config_dir, fixer_path, php_path):
    """Return the command line that runs fixer, or None when not found."""
    if fixer_path:
        return [os.path.expanduser(fixer_path)]

    # Composer's bin proxies are PHP scripts, so run them through PHP; this
    # works on Windows too.
    if config_dir:
        for d in _upward(config_dir):
            script = os.path.join(d, 'vendor', 'bin', fixer)
            if os.path.isfile(script):
                return [php_path or 'php', script]

    path = shutil.which(fixer)
    if path:
        return [path]
    return None
