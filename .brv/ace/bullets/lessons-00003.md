<!--
WARNING: Do not rename this file manually!
File name: lessons-00003.md
This file is managed by ByteRover CLI. Only edit the content below.
Renaming this file will break the link to the playbook metadata.
-->

Mutation testing with Pest v4/Infection on PHP 8.5 triggers a bug where dynamic test classes are prefixed with P\ or P. causing TestFileNameNotFoundException. Resolved by switching to PHP 8.4 for mutation runs and implementing recursive XML sanitization of JUnit and coverage reports.