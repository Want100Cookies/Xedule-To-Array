# DEPRECATED
This project is deprecated because Xedule has decided to close down it's API

# Xedule-To-Array
Convert any schedule from roosters.xedule.nl to an array

*Note: the paremeters named below must be entered in the given order*

## Methods
- [constructor](#constructor)
- [getAllOrgs](#getallorgs)
- [getOrgID](#getorgid)
- [getAllLocations](#getalllocations)
- [getLocationID](#getlocationid)
- [getAllGroups](#getallgroups)
- [getGroupID](#getgroupid)
- [getGroupSchedule](#getgroupschedule)

### constructor
The constructor can have a non required parameter with the base URL.
Standard the base URL is https://roosters.xedule.nl

### getAllOrgs
Get all the organisations

### getOrgID
Needs parameter $orgName
Get the ID from the organisation with the given name

### getAllLocations
Needs parameter $orgId
Get all the locations for the organisation with the given ID

### getLocationID
Needs parameters $locationName, $orgId
Get the ID from the location with the given name

### getAllGroups
Needs parameter $locationID
Get all groups from the location with the given ID

### getGroupID
Needs parameters $groupName, $locationID
Get the ID from the group with the given Name

### getGroupSchedule
Needs parameters $groupId, $week, $year
Get any schedule in the following format:
```php
array(
	array(
		'DESCRIPTION' 	=> 'some class'
		'START' 		=> Carbon Object,
		'END' 			=> Carbon Object
	),
	array(
		'DESCRIPTION' 	=> 'some class'
		'START' 		=> Carbon Object,
		'END' 			=> Carbon Object
	)
)
```
