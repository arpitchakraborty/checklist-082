How to use?
  * Check-out / export the project to web accessible directory in your PC or server
  * You're basically can use it already, by viewing http://localhost/checklist/ (this depend on where u put that checked-out folder)
  * You may want to adjust these variables:
    * $timezone - PHP timezone identifier
    * $per\_page - num of tasks per page

Run as portable app
  * Install xampplite on your flash drive
  * Edit /apache/conf/httpd.conf, set "Listen 80" to different value (e.g 8080)
  * Check-out / export this project to /xampplite/htdocs
  * Start xampp, then browse to http://localhost:8080/checklist/, it should be ready for use