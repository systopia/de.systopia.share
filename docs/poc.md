# Proof of Concept

As of December 2024, this extension is in a Proof of Concept (POC) state and has some API 3 actions for setting up a
defined scenario.


## Scenario

The POC aims to represent a hierarchical set of nodes with defined actions on each hierarchy level.

* *Central* node - the primary data storage which can receive changes from other nodes and distributes all changes to
  all other nodes. Imagine a national level of an association with subdivisions.
* *Intermediate* node - imagine a regional subdivision. For this POC, this level should only receive changes from the
  *Central* node and not distribute changes mode in the node itself (i.e. not create changes in the first place).
* *Leaf* node - the lowest level, imagine a local subdivision. This is where most data changes will occur, as contacts
  of the association are being maintained by local officials.

For a first demonstration, consider the following chain of events:

1. A contact is created on the *Leaf* node
2. The change is being submitted to the *Central* node
3. The change is being processed on the *Central* node, resulting in a new contact on the *Central* node
4. The *Central* node distributes the new contact to all lower nodes (*Intermediate* and *Leaf*)
5. The *Intermediate* node processes the change and just creates the contact
6. The *Leaf* node identifies the initial contact and applies changes, if any (imagine the *Central* node adding some
    metadata that have not been present when the contact was created on the *Leaf* node)
7. Eventually, there will be a detection of circular changes, but currently relies on no changes being recorded on the
  *Leaf* node when the initial change comes back from the *Central* node, and thus not creating a new local change
  record.


## Setup

1. Install the extension on all three instances. Do not forget to install dependencies with `composer update` in the
    extension directory (currently only the `civimrf/cmrf_abstract_core` package).
2. Run the `CiviSharePOC.setup_central` API3 action on the *Central* node, passing credentials to the other nodes via an
    INI file path in the `config_file_path` parameter. Note the shared secrets being generated for the peerings to the
    other nodes.
3. Run the `CiviSharePOC.setup_intermediate` API3 action on the *Intermediate* node, passing credentials to the other nodes via an
    INI file path in the `config_file_path` parameter, and the shared secret for the peering with the *Central* node in
    the `shared_secret_central` parameter.
4. Run the `CiviSharePOC.setup_leaf` API3 action on the *Leaf* node, passing credentials to the other nodes via an
    INI file path in the `config_file_path` parameter, and the shared secret for the peering with the *Central* node in
    the `shared_secret_central` parameter.


## Demonstration

1. Create a contact on the *Leaf* node. You may inspect the created change record via the `ShareChange.get` API4 action.
2. Send the changes to peered nodes (which is the *Central* node only according to the setup) with the
    `ShareChangeMessage.send` API4 action, passing `1` as the `sourceNodeId` parameter (this should match the
    *ShareNode* id of the the *Leaf* node on the *Leaf* environment). You may inspect the submitted changes on the
    *Central* node via the `ShareChange.get` API4 action.
3. Process the changes on the *Central* node with the `ShareChange.process` API4 action, passing `1` as the
    `localNodeId` parameter (this should match the *ShareNode* id of the *Central* node on the *Central* environment). A
    new contact should be created and get assigned a contact identity with the contact ID of the *Leaf* node
    (representing the peering of both contacts). Also, a new local change record should be created, as the creation of a
    contact is itself a change on the *Central* environment. You may inspect the created change record via the
    `ShareChange.get` API4 action.
4. Distribute the local changes to peered nodes (i.e. the *Intermediate* and the *Leaf* node) with the
    `ShareChangeMessage.send` API4 action, passing `1` as the `sourceNodeId` parameter (this should match the
    *ShareNode* id of the *Central* node on the *Central* environment). You may inspect the submitted changes on the
    *Intermediate* and *Leaf* nodes via the `ShareChange.get` API4 action.
5. Process changes on the *Intermediate* node with the `ShareChange.process` API4 action, passing `1` as the
    `localNodeId` parameter (this should match the *ShareNode* id of the *Intermediate* node on the *Intermediate*
    environment). A new contact should be created and get assigned a contact identity with the contact ID of the
    *Central* node (representing the peering of both contacts). A new change record will also be created, but is
    irrelevant, as the *Intermediate* environment is not supposed to distribute changes.
6. Process changes on the *Leaf* node with the `ShareChange.process` API4 action, passing `1` as the
    `localNodeId` parameter (this should match the *ShareNode* id of the *Leaf* node on the *Leaf* environment). The
    initial contact should be identified and get assigned a contact identity with the contact ID of the *Central* node
    (representing the peering of both contacts). No further update should be done and thus no new change record be
    created, so the chain ends here.

For updating a contact, the chain of actions is the same. Peered contacts (those with a contact identity of the node
from which changes are received) are directly identified. Other contacts are being identified by the attributes sent in
the changes. If no contact can be identified, a new contact will be created and peered with the source contact by adding
a contact identity. The next update of such contacts should not cause duplicates anymore.
