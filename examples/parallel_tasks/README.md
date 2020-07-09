# Parallel tasks.

This example will demonstrate how to execute multiple tasks in the
background and return the results. The use case scenario is when
you have multiple time-consuming tasks to execute, you can 
crush the execution time by running these functions in parallel 

let's say sending 3 emails can take up to 5-10 seconds each.

Instead of waiting 30 seconds for all emails to send, you can
wait 5-10 seconds for all three emails to send at the same time
and get the results as soon as all tasks are done.

## Files
 - frontend.php
 - start_server.php
 - worker.php


### frontend.php

This file is the simulation of what a front-end script would 
execute if you receive a HTTP request. Simply run `frontend.php`
after you run `start_server.php`

This script will run two tasks, each task takes 5 seconds to
complete. The purpose of this script is to run both of these
tasks at the same time in the background and wait for them to
complete and get their results.

Without BackgroundWorker, this process would take 10 seconds.

But in this example, this should only take 5 seconds instead of 10.

### start_server.php

This file starts the workers, in the source code you can configure
how many instances should be running in the background. The more
instances the more workers that are available to run a task.

When all workers are busy, gearman will hold the jobs in memory
and wait for a worker to be available before processing the job.

For this example, at least 2 workers needs to be running.


### worker.php

This script is where the time-consuming function gets executed.
the only available function is `send_email` and `spam_email`.
There are both the same function but return different results.
the purpose of this function is to simulates the
amount of time it takes to send an email and what results it should
return if successful.

**For this example, two instances are needed.** because two
parallel tasks would be executed in `frontend.php`. If only one
instance is running then only one worker would be available
to process two tasks. which would take 10 seconds.


## How to use it

First run `start_server.php`, this will start 10 instances of `worker.php`
to run in the background.

Then when the workers are ready, execute `frontend.php` to simulate what
happens when you receive a HTTP request.

You should get two outputs

`string(1) "5"` and `string(10) "1594320233"` which are the results
returned from `worker.php`. And this script should not take more
than 10 seconds to execute.

Additionally, you can just run two instances of `worker.php` in
your command-line interface and watch the output of the workers.
