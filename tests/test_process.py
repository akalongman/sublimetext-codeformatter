import os
import subprocess
import sys
import time

import pytest
from unittest.mock import Mock, call, patch


CMD = ['/opt/bin/fixer', '--no-ansi', '-']
POSIX_ONLY = pytest.mark.skipif(
    os.name == 'nt', reason='starts a POSIX shell wrapper')


def run(popen, out=b'', err=b'', returncode=0, cwd='/proj',
        platform='linux'):
    from codeformatter import process
    popen.return_value.communicate.return_value = (out, err)
    popen.return_value.returncode = returncode
    return process.run(CMD, b'<?php\n', cwd, platform)


def windows_constants(startupinfo):
    # The Windows-only subprocess names, faked so this runs on any OS.
    return patch.multiple('codeformatter.process.subprocess', create=True,
                          STARTUPINFO=Mock(return_value=startupinfo),
                          STARTF_USESHOWWINDOW=1, SW_HIDE=0)


def time_out(popen):
    popen.return_value.pid = 4242
    popen.return_value.communicate.side_effect = [
        subprocess.TimeoutExpired(CMD, 10)]


def real_platform():
    return 'osx' if sys.platform == 'darwin' else 'linux'


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
    assert popen.call_args[1]['start_new_session'] is True
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
def test_run_timeout_kills_the_group_and_reaps(popen):
    from codeformatter import process
    time_out(popen)
    p = popen.return_value
    # killpg reports into p's call list, so the order is checked too.
    with patch('codeformatter.process.os.killpg', create=True,
               side_effect=p.killpg):
        with patch('codeformatter.process.signal.SIGKILL', 9, create=True):
            with pytest.raises(process.Timeout):
                run(popen)

    assert process.TIMEOUT == 10
    assert p.mock_calls == [
        call.communicate(b'<?php\n', timeout=10), call.killpg(4242, 9),
        call.wait(), call.stdin.close(), call.stdout.close(),
        call.stderr.close()]


@patch('codeformatter.process.subprocess.Popen')
def test_run_timeout_after_the_process_exited(popen):
    from codeformatter import process
    time_out(popen)
    gone = ProcessLookupError(3, 'No such process')
    popen.return_value.kill.side_effect = gone
    with patch('codeformatter.process.os.killpg', create=True,
               side_effect=gone):
        with patch('codeformatter.process.signal.SIGKILL', 9, create=True):
            with pytest.raises(process.Timeout):
                run(popen)

    # Without a group the process alone is killed, then reaped as usual.
    assert popen.return_value.mock_calls == [
        call.communicate(b'<?php\n', timeout=10), call.kill(), call.wait(),
        call.stdin.close(), call.stdout.close(), call.stderr.close()]


@patch('codeformatter.process.subprocess.Popen')
def test_run_timeout_on_windows_kills_and_reaps(popen):
    from codeformatter import process
    time_out(popen)
    with windows_constants(Mock(dwFlags=0)):
        with patch('codeformatter.process.os.killpg', create=True) as killpg:
            with pytest.raises(process.Timeout):
                run(popen, platform='windows')

    # No close: the timed-out communicate() can leave threads blocked on the
    # pipes, and closing a pipe would wait for them.
    assert popen.return_value.mock_calls == [
        call.communicate(b'<?php\n', timeout=10), call.kill(), call.wait()]
    killpg.assert_not_called()


@POSIX_ONLY
def test_run_timeout_bounds_a_wrapper(monkeypatch):
    # sh forks sleep, which keeps the pipes open after sh is killed.
    from codeformatter import process
    monkeypatch.setattr(process, 'TIMEOUT', 0.5)
    start = time.monotonic()

    with pytest.raises(process.Timeout):
        process.run(['sh', '-c', 'sleep 5; true'], b'', None,
                    real_platform())

    assert time.monotonic() - start < 2.5


@POSIX_ONLY
def test_run_timeout_kills_what_the_wrapper_started(tmp_path, monkeypatch):
    from codeformatter import process
    monkeypatch.setattr(process, 'TIMEOUT', 0.3)
    marker = tmp_path / 'survived'
    start = time.monotonic()

    with pytest.raises(process.Timeout):
        process.run(['sh', '-c', '(sleep 1; touch "$1") & wait', 'sh',
                     str(marker)], b'', None, real_platform())

    # A subshell that survived the timeout creates the marker one second in.
    time.sleep(max(0, start + 1.5 - time.monotonic()))
    assert not marker.exists()


@patch('codeformatter.process.subprocess.Popen')
def test_run_hides_window_on_windows(popen):
    startupinfo = Mock(dwFlags=0)
    with windows_constants(startupinfo):
        run(popen, platform='windows')

    assert popen.call_args[1]['startupinfo'] is startupinfo
    assert startupinfo.dwFlags & 1
    assert startupinfo.wShowWindow == 0
    assert 'start_new_session' not in popen.call_args[1]
