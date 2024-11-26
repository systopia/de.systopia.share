# Change Types

CiviShare relies on change type indentifier match incoming changes
to the appropriate modules.

## List of known change types

| Identifier                        | Description                       | Remarks                                                              |
|-----------------------------------|-----------------------------------|----------------------------------------------------------------------|
| ``civishare.change.contact.base`` | Contact base data                 | Simple attributes like ``first_name``, ``last_name``, ``birth_date`` |
| ``civishare.change.contact.tag``  | Contact tag data                  | Allows the sharing/synching of tags                                  |
| ``civishare.change.test``         | Test change for internal purposes | This is only used for testing                                        |




## Peering

Peering in CiviShare happens on two levels:
5.
6. In the configuration of such a peering, you can define which change types (see below) will be processed, and how (providing a processor implementation)

### Node Peering

Before two CiviShare nodes (i.e. CiviCRM instances with the CiviShare extension) can exchange information, they need to be connected.
For this to happen, they each have to have an entry in the ``civicrm_share_node`` table representing the other node respectively.
Both entries have to also have a unique "common secret" i.e. an identical ``auth_key`` which both nodes use to identify and authorise the connection.

5. In the configuration of such a peering, you can define which change types (see below) will be processed, and how (providing a processor implementation)

### Contact Peering

Once the system, i.e. the local node, is peered with at least one other node, you can start peering individual contacts in between those nodes. In this process the system checks for a given local contact whether this contact also exists in the other node.
If that's the case a link is established between those contacts.
In order to facilitate this peering process, there is a search task that allows you to try and peer any set of local contacts with a peered node.


##  [NEEDS UPDATE] Services (API)

The system provides two types of services:

### Peering Services

#### CiviShare.peer
This service allows you to facilitate contact peering, see above. You send a bunch of identifying criteria of the contacts you want to peer, and you will receive a status for each contact.
That status is one of:
- ``NEWLY_PEERED``: contact was identified is is now peered (usually expressed by a contact ID)
- ``INSUFFICIENT_DATA``: there is not enough data submitted to identify the contact
- ``NOT_IDENTIFIED'``: no contact could be identified
- ``AMBIGUOUS``: multiple contacts were identified
- ``ERROR``: some unforeseen error has occurred


### Change Processing Services

#### [NEEDS UPDATE] CiviShare.store_changes

Receive a new change from another (authorised) node. This function does *not process* any changes.
It is required to return as quickly as possible, as it is synchronously called from an external system.

#### [NEEDS UPDATE] CiviShare.send_changes

Send changes that are scheduled for forwarding to those node's respective ``store_changes`` API

#### CiviShare.process_changes

Process all pending changes that have been received via the ``store_change`` action.


##  [NEEDS UPDATE] Change Status

The changes (as stored in ``civicrm_share_change``) have one of the following statuses:
- ``LOCAL``: this was a locally recorded change
- ``PENDING``: this is received change, that has not been applied yet
- ``FORWARD``: this change is ready for propagation to other nodes
- ``DONE``: this change has been fully processed
- ``BUSY``: this change is currently being processed
- ``ERROR``: something went wrong in the processing of this change

A locally detected/recorded change will have the following status flow (excluding ``BUSY`` and ``ERROR``)

``LOCAL`` -> ``FORWARD`` -> ``DONE``

while an externally received change will have the following status flow

``PENDING`` -> ``FORWARD`` -> ``DONE``



## Identity Types

Identifier types are *OptionValues* and can be managed using the CiviCRM Core
administration UI for option groups at *Administration* » *System Settings* »
*Option Groups*. The option group is called *Contact Identity Types*
(`contact_id_history_type`).

Each identity type consists of a (human-readable) name, a value (which will be
the machine-readable name), an optional description, and an optional icon.

Once you set up an identity type, you will be able to add identifiers to
contacts with that type. The contact overview will have a new tab *Contact
Identities*, which is basically a table view of a multi-value *CustomGroup* that
this extension creates and utilizes. This table will contain the *CiviCRM ID* of
the contact and also the *External Identifier* value if it is set.

## [NEEDS UPDATE] Identifier Sources

If you keep track of identifiers in custom fields on your contacts (or maybe an
API synchronizes them into them), you might want to add them to *Identity
Tracker* by watching changes of values in those fields and automatically have
identifier records created. Head to *Administration* » *System Settings* »
*Identity Tracker Settings* for assigning custom fields to identity types. Once
you apply those settings, all currently existing values in those fields will be
copied into the contact identities table, as well as all subsquent changes in
those fields.
