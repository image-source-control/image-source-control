## General Rules

- Don’t change the indentation of lines that are otherwise unchanged.
- No single-use variables.
- Do not make changes to the files and directories listed in .gitignore.

## Coding Standards

- Tabs should be used at the beginning of the line for indentation, while spaces can be used mid-line for alignment.

## Strings and Translations

- Do not create or update .po and .mo files, since this is done later by our release process.
- Reuse existing strings wherever possible. A lower number of new strings help to keep the translation effort low. When you add new strings, add a section '## Translations' to your messages (pull request or commit) that explains, which existing strings you considered and why you didn’t use them.