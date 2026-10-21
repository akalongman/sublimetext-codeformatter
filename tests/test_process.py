import os
import subprocess

import pytest
from unittest.mock import Mock, call, patch


CMD = ['/opt/bin/fixer', '--no-ansi', '-']


def run(popen, out=b'', err=b'', returncode=0, cwd='/proj',
        platform='linux'):
    from codeformatter import process
    popen.return_value.communicate.return_value = (out, err)
    popen.return_value.returncode = returncode
    return process.run(CMD, b'<?php\n', cwd, platform)


@pytest.mark.parametrize('platform', ['linux', 'osx'])
@patch('codeformatter.process.subprocess.Popen')
def test_run_passes_cwd_and_pipes(popen, platform):
    run(popen, platform=platform)

    assert popen.call_args[0][0] == CMD
    assert popen.call_args[1]['cwd'] == '/proj'
    streams = [popen.call_args[1].get(name)
               for name in ('stdin', 'stdout', 'stderr')]
    assert streams == [subprocess.PIPE] * 3
    assert popen.call_args[1].get('shell') is not True
    assert 'startupinfo' not in popen.call_args[1]
    popen.return_value.communicate.assert_called_once_with(
        b'<?php\n', timeout=10)


@patch('codeformatter.process.subprocess.Popen')
def test_run_without_working_directory(popen):
    run(popen, cwd=None)

    assert popen.call_args[1]['cwd'] is None


@patch('codeformatter.process.subprocess.Popen')
def test_run_drops_forced_color(popen, monkeypatch):
    monkeypatch.setenv('FORCE_COLOR', '1')
    monkeypatch.setenv('CLICOLOR_FORCE', '1')
    monkeypatch.setenv('CODEFORMATTER_TEST_KEEP', 'kept')

    run(popen)

    env = popen.call_args[1]['env']
    assert 'FORCE_COLOR' not in env
    assert 'CLICOLOR_FORCE' not in env
    assert env['CODEFORMATTER_TEST_KEEP'] == 'kept'
    assert os.environ['FORCE_COLOR'] == '1'
    assert os.environ['CLICOLOR_FORCE'] == '1'


@patch('codeformatter.process.subprocess.Popen')
def test_run_returns_code_stdout_and_stripped_stderr(popen):
    result = run(popen, out=b'<?php\n\n', err=b'  warning: x\n\n',
                 returncode=3)

    assert result == (3, b'<?php\n\n', 'warning: x')


@patch('codeformatter.process.subprocess.Popen')
def test_run_undecodable_stderr(popen):
    result = run(popen, err=b'\xff\xfe bad', returncode=2)

    assert result == (2, b'', '�� bad')


@patch('codeformatter.process.subprocess.Popen')
def test_run_oserror_propagates(popen):
    popen.side_effect = FileNotFoundError(2, 'No such file or directory')

    with pytest.raises(FileNotFoundError):
        run(popen)


@patch('codeformatter.process.subprocess.Popen')
def test_run_timeout_kills_reaps_and_raises(popen):
    from codeformatter import process
    popen.return_value.communicate.side_effect = [
        subprocess.TimeoutExpired(CMD, 10), (b'', b'')]

    with pytest.raises(process.Timeout):
        run(popen)

    assert process.TIMEOUT == 10
    assert popen.return_value.mock_calls == [
        call.communicate(b'<?php\n', timeout=10), call.kill(),
        call.communicate()]


@patch('codeformatter.process.subprocess.Popen')
def test_run_hides_window_on_windows(popen):
    startupinfo = Mock(dwFlags=0)
    with patch.multiple('codeformatter.process.subprocess', create=True,
                        STARTUPINFO=Mock(return_value=startupinfo),
                        STARTF_USESHOWWINDOW=1, SW_HIDE=0):
        run(popen, platform='windows')

    assert popen.call_args[1]['startupinfo'] is startupinfo
    assert startupinfo.dwFlags & 1
    assert startupinfo.wShowWindow == 0
