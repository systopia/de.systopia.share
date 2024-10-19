# CiviShare (in development)

## Objective

The CiviShare will allow you to link and connect multiple CiviCRM instances
in order to share new or updated data on one instance with all linked instances.

## Modules and data types

The system will require you to link a number of modules to a connector
to define what kind of data will be exchanged. A module could either be
* a **change detector** (will detect and record changes), or
* a **change applier** (will apply recorded changes to CiviCRM)

Both modules will use identifier to define data content they will be able to
produce or process. Such an identifier could be ``civishare.change.contact.base`` to
mark contact base data.

## Queueing

Since changes could potentially be created or received in high frequency
they will be queued and then asynchronously processed or sent.

## Peering (linking instances)

Before two (or more instances) can "talk" to each other, they first need to
be "linked". That means that they exchange URLs of the endpoint to receive data
as well as a common secret (e.g. ``YlECmXR0aps2xLQszy1....``)
which they used to identify and authorise each other. They also define
what data will be shared, outgoing and incoming, using the content types.
Note that outgoing and incoming data types could differ, and even by empty
if you want one way only communication.

## Data types

In order to be able to share any conceivable kind of data
between two CiviCRM instances, the type of data is represented by
the identifier mentioned above (e.g. ``civishare.change.contact.base``).
The list of data types (i.e. identifiers) that can be sent or received will be
defined as the ones that the sender is willing to share and the
receiver is willing to process.

## Example

Consider three systems, CIVI1


## Loop detection/prevention

Consider the following scenario: A change detected on CiviCRM-1
and then sent to CiviCRM-2 will be applied there. This, in turn,
might trigger other change messages to be sent to further CiviCRMs,
and even with a slightly different data - because the actual application
of the proposed change is up to the local implementation.

In order to prevent this to trigger a perpetual message, every change data
that is processed will be adding another signature (sha512) of
the that change, and the change processor will keep a record of
all signatures it has already processed (for a sensible time frame).
Then, if a change is received that had already been processed in
a different way, it can safely be ignored.


## Change notification structure

TBD, but most likely a JSON format.


## Detecting Changes

In order to be able to share changes with other instances, they first
need to be detected. There is three conceivable ways to do this:

1. Using pre/post hooks directly: This allows the system to react
   immediately to a given change and can propagate this right away

2. Using pre/post hooks but cache the results: This way the changes
   can be aggregated, for example at the end of a process or API call.

3. Using extended logging: One alternative could be to rely on
   CiviCRM's extended logging. There, a scheduled task could sieve
   through all log entries since the last run, and extract and send out all
   relevant changes

