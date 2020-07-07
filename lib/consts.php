<?php
  define("APPLICATION", "https://github.com/yahesh/fff-signup");
  define("VERSION",     "0.8");

  define("ERROR_FUNCTION", "function");
  define("ERROR_ID",       "id");
  define("ERROR_MESSAGE",  "message");
  define("ERROR_OUTPUT",   "output");
  define("ERROR_TIME",     "time");

  define("HTTP_METHOD", strtoupper($_SERVER["REQUEST_METHOD"]));

  define("MAIL_ADMIN_VERIFY_TOKEN",    "{%ADMIN_VERIFY_TOKEN}");
  define("MAIL_CITY",                  "{%CITY}");
  define("MAIL_COUNTRY",               "{%COUNTRY}");
  define("MAIL_ISCOMPANY",             "{%ISCOMPANY}");
  define("MAIL_JOB",                   "{%JOB}");
  define("MAIL_MAIL",                  "{%MAIL}");
  define("MAIL_MESSAGE",               "{%MESSAGE}");
  define("MAIL_NAME",                  "{%NAME}");
  define("MAIL_NEWSLETTER",            "{%NEWSLETTER}");
  define("MAIL_SUBJECT",               "{%SUBJECT}");
  define("MAIL_UID",                   "{%UID}");
  define("MAIL_USER_NEWSLETTER_TOKEN", "{%USER_NEWSLETTER_TOKEN}");
  define("MAIL_USER_VERIFY_TOKEN",     "{%USER_VERIFY_TOKEN}");
  define("MAIL_WEBSITE",               "{%WEBSITE}");
  define("MAIL_REFERENCES",            "{%INDUSTRY_REFERENCES}");

  define("MAILGUN_BATCH_SIZE", 1000);

