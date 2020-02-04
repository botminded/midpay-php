# MidPay 

## Directories

`src/adapters`

Adapter code for different payment gateways.

`src/lib`

Project agnostic library code. 

`src/modules`

Project specific library code. Deals with business logic. Can be called `models` too.

TODO:
- Finish the adapters module. The finished product will have a command line script that will 
scan through the adapters directory and update the database accordingly.

`src/api`

API routing and CRUD code. 

`src/daemons`

Daemons code. For callbacks.
TODO:
- Daemon for pinging of gateways for missing/timeouted callbacks.

`src/scrap`

Scrap code. Not for production!

`tests`

Not done. Right now, the code is heavily changing and still in a very rapid-prototyping stage.
Currently, incremental tests are just quick and dirty snippets in `src/scrap`.

