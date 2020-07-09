# Background Tasks

This example demonstrates how to offload time-consuming tasks to the
background and not let your front-end code wait until the process is 
completed.

## Files
 - frontend.php
 - start_server.php
 - worker.php


### frontend.php

This file is the simulation of what a front-end script would 
execute if you receive a HTTP request. Simply run `frontend.php`
after you run `start_server.php`

### start_server.php

This file starts the workers, in the source code you can configure
how many instances should be running in the background. The more
instances the more workers that are available to run a task.

When all workers are busy, gearman will hold the jobs in memory
and wait for a worker to be available before processing the job.


### worker.php

This script is where the time-consuming function gets executed.
the only available function is `send_email` which simulates the
amount of time it takes to send an email, this task is preferably
done in the background so that the user doesn't wait for the server
to send the email 


## How to use it

First run `start_server.php`, this will start 10 instances of `worker.php`
to run in the background.

Then when the workers are ready, execute `frontend.php` to simulate what
happens when you receive a HTTP request that needs to offload a 
time-consuming task in the background

If it works successfully, you will see `This will show even if the worker is sleeping for 5 seconds.`
being printed out while one of the workers are busy processing
the request.

**You don't need to run start_server.php**, you can just run
`worker.php` and then run `frontend.php`. `worker.php` will output
text as soon as it recieves a job from `frontend.php` and you can
see how the process really works.