Create User 'sketchspace'@'%' Identified By 'sketchspace';

Flush Privileges;

Grant All Privileges On sketchspace.* To sketchspace@'%';

Flush Privileges;

Create Database If Not Exists sketchspace;
