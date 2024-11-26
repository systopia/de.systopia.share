# CiviShare (in development)

## Objective

The CiviShare will allow you to link and connect multiple CiviCRM instances
in order to share new or updated data on one instance with all linked instances.

## Setup

In order to set up a CiviShare connection between two (or more) CiviCRM's the following has
to happen:
1. CiviShare needs to be installed on both CiviCRM instances
2. On both CiviCRM instances
  * a "local node" representing the host system is created.
  * a "remote node" representing the respective other system needs to be created along with
3. On both CiviCRM instances a local node represents the host system, and the remote nodes represent the different remote nodes it is linked to.
4. In order for two such nodes (a local and a remote one) to exchange data, these nodes then need to be peered, see section below.


## Modules and data types

The system will require you to link a number of change types to a peering
to define what kind of data will be exchanged. A module could either be
* a **change detector** (will detect and record changes), or
* a **change processor** (will apply transmitted changes to the local CiviCRM)

Both modules will use identifiers to define data content they will be able to
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


## Example

Consider two systems, ``CIVI1``, ``CIVI2``. If you want to simply pass
selected contact changes and newly created contacts from ``CIVI1`` to ``CIVI2``
you'd:
1. link the two instances with a common secret.
2. set up a contact base change detector on ``CIVI1``
3. set up a contact base change processor on ``CIVI2``

You should be able to see detected changes from ``CIVI1`` sent to ``CIVI2``
and applied there.

If you would also detect and send changes from ``CIVI2`` to ``CIVI1`` the
loop detection outlined above should kick in, so there would't be an eternal
change back-and-forth.

## Change message structure example

```json
{
  "id": "some-unique-id",
  "payload_signature": "/HbL/n2GaZWex15bmNquRUYWUriGEPpncqcIUQkqgwoltCzQU+x2IjMZZNgSFJ2oMJBk24AzHn/WZw8eOn5RPX2frgjtPtR1FO24H7YqD8X59rZMBHgRN+4TYl+hJjo8pEpgTQvp0WMmV8DZNEVZBjmwdmtlJ4e/f5SNWRi2kNQ=",
  "payload": {
    "sender": "https://node4.mydistributed.org",
    "sent": "2024-10-23 20:10:00 CEST"
    "changes": [
      {
        "type": "civishare.change.contact.base",
        "timestamp": "2024-10-23 20:08:48 CEST",
        "entity": "Contact",
        "entity_reference": "312",
        "attribute_changes": [
          {
            "name": "first_name",
            "from": "Karl",
            "to": "Carl"
          },
          {
            "name": "birth_date",
            "from": "",
            "to": "2000-01-01"
          }
        ]
        "loop_detection": ["5R+hJjo8pEpgTQvp0WMmV8DZNEVZB"]
      },
      {
        "type": "civishare.change.contact.base",
        "timestamp": "2024-10-23 20:08:48 CEST",
        "entity": "Contact",
        "entity_reference": "2312",
        "attribute_changes": [
          {
            "name": "first_name",
            "from": "Karlotta",
            "to": "Escarlata"
          },
          {
            "name": "last_name",
            "from": "",
            "to": "La Pirata"
          }
        ],
        "loop_detection": ["5RPX2frgjtPtR1FO24H7YqD8X59rZMBHgRN+4TYl+hJjo8pEpgTQvp0WMmV8DZNEVZB","5RPX2frgjtPtR1FO2asdwqwewqe+4TYl+hJjo8pEpgTQvp0WMmV8DZNEVZB"]
      }
    ]
  }
}
```


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

