# scratch#01

## list of quality checklists for `/speckit`

- accessibility
- architecture
- bdd
- caching
- compliance
- configuration
- data integrity
- documentation
- edge cases
- environment
- implementation
- observability
- performance
- quality assurance
- release gate(s)
- reliability
- requirements
- security
- task quality
- tdd
- test plans
- tools
- ui and ux

---

## translations

  create translations for the following:

  - pt_BR
  - zh_CN
  - de_DE
  - es_ES
  - fr_FR
  - en_GB
  - it_IT
  - ja_JP
  - ko_KR
  - es_MX
  - nl_NL
  - fl_NL
  - ru_RU
  - tr_TR
  - en_US
  - vi_VN

---

## Key / Protected Roles

- Admin
- Customer
- Deputy
- Executive
- Guest
- Host
- Manager
- Member
- Owner
- Partner
- Subscriber
- User
- Vendor
- Visitor

Consider enterprise configurable additions to this list
Consider use of an ENUM to enforce
Consider adding `Admin` to the list
Consider: the constraint should apply when there is only a single model, the last, with the role

---

## Laravel Extension Issues

  Debug Info

  {
    "os": "darwin",
    "arch": "arm64",
    "php_version": "8.5.1",
    "laravel_version": "12.44.0",
    "php_command": "/Users/s-a-c/Library/Application Support/Herd/bin/php"
  }

---

Custom Blade Directives

Error:
In FolioServiceProvider.php line 23:

App\Providers\FolioServiceProvider::boot() has #[\Override] attribute, but
no matching parent method exists

---

## Improvements

  - Enhance/Expand Use of DTOs
  - Enhance/Expand use of Traits
  - Analyze for Complexity Reduction
  - Enhance/Expand Use of Functional Programming Paradigm
  - Analyze for Domain and or Modular Boundaries
  - Convert "try / catch" to "error as value"

## Laravel Extension Error

  > Debug Info
  > {
  >   "os": "darwin",
  >   "arch": "arm64",
  >   "php_version": "8.5.1",
  >   "laravel_version": "12.44.0",
  >   "php_command": "\"/Users/s-a-c/Library/Application Support/Herd/bin/php\""
  > }
  > ----------------------------------------
  > Eloquent Attributes and Relations
  > Error: __VSCODE_LARAVEL_START_OUTPUT__
  >    Symfony\Component\ErrorHandler\Error\FatalError
  >   A method with return type must return a value (did you mean "return null;" instead of "return;"?)
  >   at app/Models/Enterprise.php:81
  >      77▕         } finally {
  >      78▕             if ($originalTenant) {
  >      79▕                 tenancy()->initialize($originalTenant);
  >      80▕
  >   ➜  81▕                 return;
  >      82▕             }
  >      83▕
  >      84▕             tenancy()->end();
  >      85▕         }
  >    Whoops\Exception\ErrorException
  >   A method with return type must return a value (did you mean "return null;" instead of "return;"?)
  >   at app/Models/Enterprise.php:81
  >      77▕         } finally {
  >      78▕             if ($originalTenant) {
  >      79▕                 tenancy()->initialize($originalTenant);
  >      80▕
  >   ➜  81▕                 return;
  >      82▕             }
  >      83▕
  >      84▕             tenancy()->end();
  >      85▕         }
  >       [2m+1 vendor frames [22m
  >   2   [internal]:0
  >       Whoops\Run::handleShutdown()
