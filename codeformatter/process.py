import os
import subprocess


TIMEOUT = 10


class Timeout(Exception):
    """The tool did not finish in TIMEOUT seconds and was killed."""


def run(cmd, text, cwd, platform):
    """Run cmd in cwd with text on stdin.

    Returns (returncode, stdout bytes, stderr decoded and stripped). An
    OSError from starting the process propagates to the caller.
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

    p = subprocess.Popen(
        cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
        stderr=subprocess.PIPE, **popen_args)

    try:
        stdout, stderr = p.communicate(text, timeout=TIMEOUT)
    except subprocess.TimeoutExpired:
        p.kill()
        p.communicate()
        raise Timeout()

    return p.returncode, stdout, stderr.decode('utf-8', 'replace').strip()
