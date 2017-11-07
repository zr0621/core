@insulated
Feature: Share by public link

As an user
I would like to share files through a public link
So that users, who do not have an account on my oC server can access them

As an admin
I would like to limit the ability of a user to share files/folders through a public link
So that the user is forced to obey the policies of the server operator

	Background:
		Given these users exist:
		|username|password|displayname|email       |
		|user1   |1234    |User One   |u1@oc.com.np|
		|user2   |1234    |User Two   |u2@oc.com.np|
		|user3   |1234    |User Three |u2@oc.com.np|
		And I am on the login page
		And I login with username "user1" and password "1234"

	Scenario: simple sharing by public link
		And I create a new public link for the folder "simple-folder" with
		| name       | new name     |
		| permission | Read & Write |
		| password   | secret       |
		| expiration | 31-12-2017   |
		| email      | me@oc.com.np |
		And I create a new public link for the folder "simple-folder" with
		| permission | Read & Write |
		| expiration | 31-12-2017   |
		| email      | me@oc.com.np |
		And I create a new public link for the folder "simple-folder"
		Then the file "lorem.txt" should be listed through the public link