import os
import signal
import subprocess


TIMEOUT = 10


class Timeout(Exception):
    """The tool did not finish in TIMEOUT seconds and was killed."""


def run(cmd, text, cwd, platform):
    """Run cmd in cwd with text on stdin.

    Returns (returncode, stdout bytes, stderr decoded and stripped). An
    OSError from starting the process propagates to the caller. After
    TIMEOUT seconds the process is killed and reaped and Timeout is raised.
    """
    # An inherited FORCE_COLOR or CLICOLOR_FORCE would put ANSI escape codes
    # in the output and in the error dialog.
    env = os.environ.copy()
    env.pop('FORCE_COLOR', None)
    env.pop('CLICOLOR_FORCE', None)

    popen_args = {'cwd': cwd, 'env': env}
    if platform == 'windows':
        startupinfo = subprocess.STARTUPINFO()
        startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW
        startupinfo.wShowWindow = subprocess.SW_HIDE
        popen_args['startupinfo'] = startupinfo
    else:
        # Its own session makes the process a group leader, so a timeout
        # also kills what a wrapper script started.
        popen_args['start_new_session'] = True

    p = subprocess.Popen(
        cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
        stderr=subprocess.PIPE, **popen_args)

    try:
        stdout, stderr = p.communicate(text, timeout=TIMEOUT)
    except subprocess.TimeoutExpired:
        _kill(p, platform)
        raise Timeout()

    return p.returncode, stdout, stderr.decode('utf-8', 'replace').strip()


def _kill(p, platform):
    """Kill and reap p without reading the rest of its output.

    A second communicate() would read the pipes to the end, and a process
    the tool started (php.exe under a .bat proxy, the fixer under a shell
    wrapper) keeps them open, so the wait would outlast the timeout.
    """
    if platform == 'windows':
        p.kill()
        p.wait()
        # The timed-out communicate() can leave threads blocked on the pipes
        # (reading stdout and stderr, and on newer Pythons writing stdin),
        # and closing a pipe would wait for that call to return. They are
        # daemon threads, and end when the process at the other end does.
        return

    try:
        os.killpg(p.pid, signal.SIGKILL)
    except OSError:
        # No group by that id: kill the process alone if it still runs, so
        # the wait below stays bounded.
        try:
            p.kill()
        except OSError:
            pass
    p.wait()
    for pipe in (p.stdin, p.stdout, p.stderr):
        if pipe is not None:
            try:
                pipe.close()
            except OSError:
                pass
