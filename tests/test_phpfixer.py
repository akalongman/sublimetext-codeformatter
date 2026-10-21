import pytest
from unittest.mock import patch


def touch(path):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text('')
    return path


def find_config(file_name, forced=None):
    from codeformatter import phpfixer
    return phpfixer.find_config(
        None if file_name is None else str(file_name), forced)


def find_executable(fixer, config_dir, fixer_path='', php_path=''):
    from codeformatter import phpfixer
    return phpfixer.find_executable(
        fixer, None if config_dir is None else str(config_dir),
        fixer_path, php_path)


@pytest.mark.parametrize('fixer,name', [
    ('pint', 'pint.json'),
    ('php-cs-fixer', '.php-cs-fixer.php'),
    ('php-cs-fixer', '.php-cs-fixer.dist.php'),
    ('phpcbf', 'phpcs.xml'),
    ('phpcbf', '.phpcs.xml'),
    ('phpcbf', 'phpcs.xml.dist'),
    ('phpcbf', '.phpcs.xml.dist'),
])
def test_config_file_names(tmp_path, fixer, name):
    proj = tmp_path / 'proj'
    touch(proj / name)
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php) == (fixer, str(proj), str(proj / name))


def test_nearest_config_wins(tmp_path):
    proj = tmp_path / 'proj'
    touch(proj / 'phpcs.xml')
    touch(proj / 'sub' / 'pint.json')
    php = touch(proj / 'sub' / 'x' / 'a.php')

    assert find_config(php) == (
        'pint', str(proj / 'sub'), str(proj / 'sub' / 'pint.json'))


def test_tie_order(tmp_path):
    proj = tmp_path / 'proj'
    pint_json = touch(proj / 'pint.json')
    touch(proj / '.php-cs-fixer.dist.php')
    touch(proj / 'phpcs.xml')
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php) == ('pint', str(proj), str(pint_json))

    pint_json.unlink()

    assert find_config(php) == (
        'php-cs-fixer', str(proj), str(proj / '.php-cs-fixer.dist.php'))


def test_pint_fallback(tmp_path):
    proj = tmp_path / 'proj'
    touch(proj / 'composer.json')
    touch(proj / 'vendor' / 'bin' / 'pint')
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php) == ('pint', str(proj), None)


def test_pint_fallback_uses_nearest_composer_dir(tmp_path):
    parent = tmp_path / 'parent'
    touch(parent / 'composer.json')
    touch(parent / 'vendor' / 'bin' / 'pint')
    touch(parent / 'proj' / 'composer.json')
    php = touch(parent / 'proj' / 'src' / 'a.php')

    assert find_config(php) is None


def test_no_config(tmp_path):
    php = touch(tmp_path / 'proj' / 'src' / 'a.php')

    assert find_config(php) is None


def test_forced_uses_its_own_config(tmp_path):
    parent = tmp_path / 'parent'
    touch(parent / 'phpcs.xml')
    touch(parent / 'proj' / 'pint.json')
    php = touch(parent / 'proj' / 'src' / 'a.php')

    assert find_config(php, 'phpcbf') == (
        'phpcbf', str(parent), str(parent / 'phpcs.xml'))


def test_forced_without_config(tmp_path):
    proj = tmp_path / 'proj'
    composer_json = touch(proj / 'composer.json')
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php, 'pint') == ('pint', str(proj), None)

    composer_json.unlink()

    assert find_config(php, 'pint') == ('pint', str(proj / 'src'), None)


def test_unsaved_buffer():
    assert find_config(None) is None
    assert find_config(None, 'pint') == ('pint', None, None)


def test_executable_fixer_path(tmp_path, monkeypatch):
    monkeypatch.setenv('HOME', '/home/u')
    monkeypatch.setenv('USERPROFILE', '/home/u')
    proj = tmp_path / 'proj'
    touch(proj / 'vendor' / 'bin' / 'pint')

    assert find_executable('pint', proj, fixer_path='~/bin/pint') == [
        '/home/u/bin/pint']


def test_executable_vendor_bin(tmp_path):
    proj = tmp_path / 'proj'
    script = str(touch(proj / 'vendor' / 'bin' / 'phpcbf'))

    assert find_executable('phpcbf', proj, php_path='') == ['php', script]
    assert find_executable('phpcbf', proj, php_path='/opt/php') == [
        '/opt/php', script]


def test_executable_vendor_bin_above_config(tmp_path):
    root = tmp_path / 'root'
    touch(root / 'vendor' / 'bin' / 'phpcbf')
    config_dir = root / 'packages' / 'a'
    config_dir.mkdir(parents=True)

    assert find_executable('phpcbf', config_dir) == [
        'php', str(root / 'vendor' / 'bin' / 'phpcbf')]


def test_executable_path_lookup(tmp_path):
    proj = tmp_path / 'proj'
    proj.mkdir()

    with patch('codeformatter.phpfixer.shutil.which',
               return_value='/usr/local/bin/pint') as which:
        assert find_executable('pint', proj) == ['/usr/local/bin/pint']
        # A never-saved buffer has no configuration directory to walk from.
        assert find_executable('pint', None) == ['/usr/local/bin/pint']

    assert which.call_args_list == [(('pint',),), (('pint',),)]


def test_executable_missing(tmp_path):
    proj = tmp_path / 'proj'
    proj.mkdir()

    with patch('codeformatter.phpfixer.shutil.which', return_value=None):
        assert find_executable('php-cs-fixer', proj) is None
        assert find_executable('php-cs-fixer', None) is None
