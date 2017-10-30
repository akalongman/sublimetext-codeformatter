Contributing
-------------

Before you contribute code to this project, please make sure it conforms to the project coding standards
and that the project unit tests still pass. The easiest way to contribute is to work on a checkout of the repository,
or your own fork. If you do this, you can run the following commands to check if everything is ready to submit:

    cd project
    pip install -r dev-requirements.pip
    pytest

Which should give you no failures or errors. You can ignore any skipped tests as these are for external tools.

Pushing
-------

Development is based on the git flow branching model (see http://nvie.com/posts/a-successful-git-branching-model/ )
If you fix a bug please push in hotfix branch.
If you develop a new feature please create a new branch.

Version
-------
Version number: 0.#version.#hotfix
