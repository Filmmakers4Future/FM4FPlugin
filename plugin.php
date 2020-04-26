<?php

  /**
    This is the Filmmakers for Future plugin.

    This file contains the plugin class of the Filmmakers for Future plugin.

    @package filmmakers4future\fm4fplugin
    @version 0.1a0
    @author  Yahe <hello@yahe.sh>
    @since   0.1a0
  */

  // ===== DO NOT EDIT HERE =====

  // prevent script from getting called directly
  if (!defined("URLAUBE")) { die(""); }

  class FM4FPlugin implements Plugin {

    // CONSTANTS

    const FM4FCONTACT         = "[fm4fcontact]";
    const FM4FNEWSLETTER      = "[fm4fnewsletter]";
    const FM4FREGISTER        = "[fm4fregister]";
    const FM4FSEND            = "[fm4fsend]";
    const FM4FSIGNATURES      = "[fm4fsignatures]";
    const FM4FSIGNATURESCOUNT = "[fm4fsignaturescount]";
    const FM4FSIGNATURESDATE  = "[fm4fsignaturesdate]";
    const FM4FVERIFY          = "[fm4fverify]";
    const FM4FVIDEOS          = "~\[fm4fvideos (?P<videos>[0-9A-Za-z\_\-]+)\]~";

    const VIDEOS = "videos";

    // FIELDS

    protected static $subscribed       = null;
    protected static $subscribed_error = null;
    protected static $verified         = null;
    protected static $verified_error   = null;

    // HELPER FUNCTIONS

    protected static function configure() {
      // this is mail address where administrative mails will be sent to
      Plugins::preset("ADMIN_MAIL", "root@localhost");

      // this is the base URL expected in REFERER headers sent by browsers when submitting a form,
      // this value is used to check the actual REFERER header during a form submission,
      // the check is used to prevent cross-site request forgery attempts
      Plugins::preset("BASE_URL", absoluteurl(value(Main::class, "/")));

      // this is the MailGun configuration needed to send mails
      Plugins::preset("MAILGUN_AUTH",     "api:key-0123456789abcdefghijklmnopqrstuvwxyz");
      Plugins::preset("MAILGUN_ENDPOINT", "https://api.eu.mailgun.net/v3/localhost/messages");
      Plugins::preset("MAILGUN_FROM",     "Filmmakers for Future <root@localhost>");

      // this is the newsletter submission password, it has to be set in the CRYPT password format,
      // the value can be generated via:
      // php -r 'print(str_replace("\$", "\\\$", password_hash(readline("Password: "), PASSWORD_DEFAULT)."\n"));'
      Plugins::preset("NEWSLETTER_SEND_PASSWORD", null);

      // this is the database configuration
      Plugins::preset("DB_HOST", "localhost");
      Plugins::preset("DB_PORT", 3306);
      Plugins::preset("DB_NAME", null);
      Plugins::preset("DB_USER", null);
      Plugins::preset("DB_PASS", null);

      // defines the recipients and mail subjects of messages sent through the contact form
      Plugins::preset("CONTACT_SUBJECTS", [[MAIL_MAIL    => Plugins::get("ADMIN_MAIL"),
                                            MAIL_SUBJECT => "Message about example.com"],
                                           [MAIL_MAIL    => Plugins::get("ADMIN_MAIL"),
                                            MAIL_SUBJECT => "Message about example.net"]]);

      // defines the contents of the mail that is sent to the admin DURING verification
      Plugins::preset("ADMIN_VERIFY_MAIL_BODY",    file_get_contents(USER_CONFIG_PATH."templates/admin_verify.txt"));
      Plugins::preset("ADMIN_VERIFY_MAIL_SUBJECT", "Please verify this user registration");

      // defines the contents of the mail that is sent to the admin when the contact form is used
      Plugins::preset("CONTACT_MAIL_BODY",    file_get_contents(USER_CONFIG_PATH."templates/contact.txt"));
      Plugins::preset("CONTACT_MAIL_SUBJECT", "Filmmakers for Future: {%SUBJECT}");

      // defines the default contents of the newsletter submission form
      Plugins::preset("NEWSLETTER_MAIL_BODY",    file_get_contents(USER_CONFIG_PATH."templates/newsletter.txt"));
      Plugins::preset("NEWSLETTER_MAIL_SUBJECT", "");

      // defines the contents of the mail that is sent to the user when requesting a newsletter management link
      Plugins::preset("USER_NEWSLETTER_MAIL_BODY",    file_get_contents(USER_CONFIG_PATH."templates/user_newsletter.txt"));
      Plugins::preset("USER_NEWSLETTER_MAIL_SUBJECT", "Newsletter subscription management link");

      // defines the contents of the mail that is sent to the user AFTER verification
      Plugins::preset("USER_VERIFIED_MAIL_BODY",    file_get_contents(USER_CONFIG_PATH."templates/user_verified.txt"));
      Plugins::preset("USER_VERIFIED_MAIL_SUBJECT", "Your registration has been verified!");

      // defines the contents of the mail that is sent to the user DURING verification
      Plugins::preset("USER_VERIFY_MAIL_BODY",    file_get_contents(USER_CONFIG_PATH."templates/user_verify.txt"));
      Plugins::preset("USER_VERIFY_MAIL_SUBJECT", "Please verify your registration!");

      // defines the error handling
      Plugins::preset("ERRORS_ENABLED",    true);
      Plugins::preset("ERRORS_FOLDER",     USER_CONFIG_PATH."errors/");
      Plugins::preset("ERRORS_NEWSLETTER", "Your signature has not yet been verified.");
      Plugins::preset("ERRORS_REGISTER",   "Your email address has already been used to sign our statement.");
      Plugins::preset("ERRORS_VERIFY",     "Your signature does not need to be verified.");

      // defines the list of videos
      Plugins::preset("VIDEOS", null);

      // set fff-signup sourcecode uses standard defines so we have to convert them
      static::define("ADMIN_MAIL");
      static::define("BASE_URL");
      static::define("MAILGUN_AUTH");
      static::define("MAILGUN_ENDPOINT");
      static::define("MAILGUN_FROM");
      static::define("NEWSLETTER_SEND_PASSWORD");
      static::define("DB_HOST");
      static::define("DB_PORT");
      static::define("DB_NAME");
      static::define("DB_USER");
      static::define("DB_PASS");
      static::define("CONTACT_SUBJECTS");
      static::define("ADMIN_VERIFY_MAIL_BODY");
      static::define("ADMIN_VERIFY_MAIL_SUBJECT");
      static::define("CONTACT_MAIL_BODY");
      static::define("CONTACT_MAIL_SUBJECT");
      #static::define("NEWSLETTER_MAIL_BODY");
      #static::define("NEWSLETTER_MAIL_SUBJECT");
      static::define("USER_NEWSLETTER_MAIL_BODY");
      static::define("USER_NEWSLETTER_MAIL_SUBJECT");
      static::define("USER_VERIFIED_MAIL_BODY");
      static::define("USER_VERIFIED_MAIL_SUBJECT");
      static::define("USER_VERIFY_MAIL_BODY");
      static::define("USER_VERIFY_MAIL_SUBJECT");
      static::define("ERRORS_ENABLED");
      static::define("ERRORS_FOLDER");
      static::define("ERRORS_NEWSLETTER");
      static::define("ERRORS_REGISTER");
      static::define("ERRORS_VERIFY");
    }

    protected static function define($name) {
      try_define($name, Plugins::get($name));
    }

    protected static function get_subscribed(&$error = null) {
      $result = null;

      if ((null === static::$subscribed) && (null === static::$subscribed_error)) {
        // retrieve result
        $result = get_subscribed($error);

        // store for later usage
        static::$subscribed       = $result;
        static::$subscribed_error = $error;
      } else {
        // return cached result
        $result = static::$suscribed;
        $error  = static::$subscribed_error;
      }

      return $result;
    }

    protected static function get_verified(&$error = null) {
      $result = null;

      if ((null === static::$verified) && (null === static::$verified_error)) {
        // retrieve result
        $result = get_verified($error);

        // store for later usage
        static::$verified       = $result;
        static::$verified_error = $error;
      } else {
        // return cached result
        $result = static::$verified;
        $error  = static::$verified_error;
      }

      return $result;
    }

    protected static function contact_get($content) {
      $result = $content;

      if (is_string($result)) {
        // generate HTML source code
        $html = tfhtml("<form class=\"text-center\" id=\"contact_form\" action=\"%s\" method=\"post\">".NL.
                       "  <!-- Subject -->".NL.
                       "  <label class=\"mb-1\">%s</label>".NL.
                       "  <select name=\"subject\" class=\"browser-default custom-select mb-4\" required>".NL.
                       "    <option value=\"\" disabled selected>%s</option>".NL,
                       FM4FPlugin::class,
                       value(Main::class, URI),
                       "Please select a subject for your message:",
                       "Choose option...");

        $contact_subjects = value(Plugins::class, "CONTACT_SUBJECTS");
        if (is_array($contact_subjects)) {
          for ($i = 0; $i < count($contact_subjects); $i++) {
            $html .= tfhtml("    <option value=\"%d\">%s</option>".NL,
                            FM4FPlugin::class,
                            $i,
                            $contact_subjects[$i][MAIL_SUBJECT]);

          }
        }

        $html .= tfhtml("  </select>".NL.
                        "  <!-- Name -->".NL.
                        "  <input type=\"text\" name=\"name\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\" required>".NL.
                        "  <!-- Email -->".NL.
                        "  <input type=\"email\" name=\"mail\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\" required>".NL.
                        "  <!-- Message -->".NL.
                        "  <div class=\"form-group\">".NL.
                        "    <textarea name=\"message\" rows=\"5\" placeholder=\"%s\" class=\"form-control mb-4\" required></textarea>".NL.
                        "  </div>".NL.
                        "  <!-- Privacy -->".NL.
                        "  <div class=\"custom-control custom-checkbox mb-4\">".NL.
                        "    <input type=\"checkbox\" class=\"custom-control-input\" id=\"privacy\" required>".NL.
                        "    <label class=\"custom-control-label\" for=\"privacy\">%s <a href=\"%s\" target=\"_blank\" rel=\"noopener noreferrer\">%s</a>.</label>".NL.
                        "  </div>".NL.
                        "  <!-- Send button -->".NL.
                        "  <button class=\"btn btn-info btn-block mb-1\" id=\"contact_button\" type=\"submit\">%s</button>".NL.
                        "</form>",
                        FM4FPlugin::class,
                        "Full name or company name*",
                        "Email address*",
                        "Message*",
                        "I have read and agree to the",
                        "/privacy/",
                        "privacy policy",
                        "Send message");

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FCONTACT, $html, $result);
      }

      return $result;
    }

    protected static function contact_post($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = contact($_POST, $error);

        // generate HTML source code
        $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                       "  <p>".NL,
                       FM4FPlugin::class);

        if ($output) {
          $html .= tfhtml("    <b>%s</b><br>".NL.
                          "    %s".NL,
                          FM4FPlugin::class,
                          "Thank you for contacting us.",
                          "We will come back to you as soon as possible.");
        } else {
          // generate HTML source code
          $html .= tfhtml("    <b>%s%s</b><br>".NL,
                          FM4FPlugin::class,
                          "Unfortunately, an error has occured",
                          (array_key_exists(ERROR_OUTPUT, $error)) ? ":" : ".");

          if (array_key_exists(ERROR_OUTPUT, $error)) {
            $html .= tfhtml("    %s<br>".NL,
                            FM4FPlugin::class,
                            $error[ERROR_OUTPUT]);
          }

          $html .= tfhtml("    %s <a href=\"%s\">%s</a>.".NL,
                          FM4FPlugin::class,
                          "Please try again later or",
                          "/contact/",
                          "contact us");

          if (array_key_exists(ERROR_ID, $error)) {
            $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                            FM4FPlugin::class,
                            "Please provide the following error id when contacting us about this issue:",
                            $error[ERROR_ID]);
          }
        }

        $html .= tfhtml("  </p>".NL.
                        "</div>".NL.
                        "<script src=\"%s\"></script>",
                        FM4FPlugin::class,
                        path2uri(__DIR__."/js/norepost.js"));

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FCONTACT, $html, $result);
      }

      return $result;
    }

    protected static function newsletter_get($content) {
      $result = $content;

      if (is_string($result)) {
        // check if a link was used
        if (array_key_exists("uid", $_GET) && array_key_exists("user", $_GET)) {
          $error  = [];  // has to be defined as an array
          $output = preview_newsletter($_GET, $error);

          if ($output) {
            // generate HTML source code
            $html = tfhtml("<form class=\"text-center p-5\" id=\"newsletter_form\" action=\"%s\" method=\"post\">".NL.
                           "  <p class=\"text-white-85 mb-4\">%s</p>".NL.
                           "  <label class=\"mb-3\">%s</label>".NL.
                           "  <select id=\"newsletter\" name=\"newsletter\" class=\"browser-default custom-select mb-4\" required>".NL.
                           "    <option value=\"\" disabled>%s</option>".NL.
                           "    <option value=\"0\" %s>%s</option>".NL.
                           "    <option value=\"1\" %s>%s</option>".NL.
                           "  </select>".NL.
                           "  <!-- Send button -->".NL.
                           "  <button class=\"btn btn-info btn-block mb-0\" id=\"verify_button\" type=\"submit\">%s</button>".NL.
                           "</form>",
                           FM4FPlugin::class,
                           value(Main::class, URI).querystring(),
                           "Below you can see your current subscription status. Feel free to adjust it, you can always request a new link if you change your mind.",
                           "Do you want to stay in touch?",
                           "Choose option...",
                           ($output[MAIL_NEWSLETTER]) ? "" : "selected",
                           "No updates please.",
                           ($output[MAIL_NEWSLETTER]) ? "selected" : "",
                           "Please keep me updated.",
                           "Update newsletter subscription");
          } else {
            // generate HTML source code
            $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                           "  <p>".NL.
                           "    <b>%s</b><br>".NL.
                           "    %s <a href=\"%s\">%s</a>.".NL,
                           FM4FPlugin::class,
                           "The newsletter management link you used is invalid.",
                           "Please try again later or",
                           "/contact/",
                           "contact us");

            if (array_key_exists(ERROR_ID, $error)) {
              $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                              FM4FPlugin::class,
                              "Please provide the following error id when contacting us about this issue:",
                              $error[ERROR_ID]);
            }

            $html .= fhtml("  </p>".NL.
                           "</div>");
          }
        } else {
          // generate HTML source code
          $html = tfhtml("<p class=\"text-white-85 mb-4\">%s</p>".NL.
                         "<form class=\"text-center\" id=\"newsletter_form\" action=\"%s\" method=\"post\">".NL.
                         "  <input class=\"form-control mb-4\" type=\"email\" name=\"newmail\" required maxlength=\"256\" placeholder=\"%s\">".NL.
                         "  <button class=\"btn btn-info btn-block mb-3\" id=\"newsletter_button\" type=\"submit\">%s</button>".NL.
                         "  <p class=\"text-white-85 mb-0 font-weight-light\"><b>%s</b> %s</p>".NL.
                         "</form>",
                         FM4FPlugin::class,
                         "Request a new newsletter management link:",
                         value(Main::class, URI),
                         "Email address*",
                         "Request a new link",
                         "Please note:",
                         "You can only manage your newsletter subscription if you have signed our statement and have finished the verification process. We do not offer a general newsletters.");
        }

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FNEWSLETTER, $html, $result);
      }

      return $result;
    }

    protected static function newsletter_post($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = newsletter(array_merge($_GET, $_POST), $error);

        // generate HTML source code
        $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                       "  <p>".NL,
                       FM4FPlugin::class);

        if ($output) {
          // check if a link was used
          if (array_key_exists("uid", $_GET) && array_key_exists("user", $_GET)) {
            $html .= tfhtml("    <b>%s</b><br>".NL.
                            "    %s".NL,
                            FM4FPlugin::class,
                            "Thank you for updating your newsletter subscription.",
                            "At the moment there is nothing more to do for you.");
          } else {
            $html .= tfhtml("    <b>%s</b><br>".NL.
                            "    %s<br>".NL.
                            "    %s".NL,
                            FM4FPlugin::class,
                            "Thank you for updating your newsletter subscription.",
                            "We will send you an e-mail with further instructions.",
                            "Please check your spam folder, just in case.");
          }
        } else {
          // generate HTML source code
          $html .= tfhtml("    <b>%s%s</b><br>".NL,
                          FM4FPlugin::class,
                          "Unfortunately, an error has occured",
                          (array_key_exists(ERROR_OUTPUT, $error)) ? ":" : ".");

          if (array_key_exists(ERROR_OUTPUT, $error)) {
            $html .= tfhtml("    %s<br>".NL,
                            FM4FPlugin::class,
                            $error[ERROR_OUTPUT]);
          }

          $html .= tfhtml("    %s <a href=\"%s\">%s</a>.".NL,
                          FM4FPlugin::class,
                          "Please try again later or",
                          "/contact/",
                          "contact us");

          if (array_key_exists(ERROR_ID, $error)) {
            $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                            FM4FPlugin::class,
                            "Please provide the following error id when contacting us about this issue:",
                            $error[ERROR_ID]);
          }
        }

        $html .= tfhtml("  </p>".NL.
                        "</div>".NL.
                        "<script src=\"%s\"></script>",
                        FM4FPlugin::class,
                        path2uri(__DIR__."/js/norepost.js"));

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FNEWSLETTER, $html, $result);
      }

      return $result;
    }

    protected static function register_get($content) {
      $result = $content;

      if (is_string($result)) {
        // generate HTML source code
        $html = tfhtml("<form class=\"text-center border border-light p-5\" id=\"register_form\" action=\"%s\" method=\"post\">".NL.
                       "  <!-- Name -->".NL.
                       "  <input type=\"text\" name=\"name\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\" required>".NL.
                       "  <!-- Email -->".NL.
                       "  <input type=\"email\" name=\"mail\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\" required>".NL.
                       "  <!-- Job -->".NL.
                       "  <input type=\"text\" name=\"job\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\" required>".NL.
                       "  <!-- Country -->".NL.
                       "  <input type=\"text\" name=\"country\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\" required>".NL.
                       "  <!-- City -->".NL.
                       "  <input type=\"text\" name=\"city\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\">".NL.
                       "  <!-- Website -->".NL.
                       "  <input type=\"link\" name=\"website\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\">".NL.
                       "  <!-- Individual or Company -->".NL.
                       "  <select name=\"iscompany\" class=\"browser-default custom-select mb-4\" required>".NL.
                       "    <option value=\"\" disabled selected>%s</option>".NL.
                       "    <option value=\"0\">%s</option>".NL.
                       "    <option value=\"1\">%s</option>".NL.
                       "  </select>".NL.
                       "  <!-- Newsletter -->".NL.
                       "  <label class=\"mb-1\">%s</label>".NL.
                       "  <select name=\"newsletter\" class=\"browser-default custom-select mb-4\" required>".NL.
                       "    <option value=\"\" disabled selected>%s</option>".NL.
                       "    <option value=\"0\">%s</option>".NL.
                       "    <option value=\"1\">%s</option>".NL.
                       "  </select>".NL.
                       "  <!-- Privacy -->".NL.
                       "  <div class=\"custom-control custom-checkbox mb-4\">".NL.
                       "    <input type=\"checkbox\" class=\"custom-control-input\" id=\"privacy\" required>".NL.
                       "    <label class=\"custom-control-label\" for=\"privacy\">%s <a href=\"%s\" target=\"_blank\" rel=\"noopener noreferrer\">%s</a>.</label>".NL.
                       "  </div>".NL.
                       "  <!-- Send button -->".NL.
                       "  <button class=\"btn btn-info btn-block mb-1\" id=\"register_button\" type=\"submit\">%s</button>".NL.
                       "</form>",
                       FM4FPlugin::class,
                       value(Main::class, URI)."#sign",
                       "Full name or company name*",
                       "Email address*",
                       "Job title or company field*",
                       "Country*",
                       "City",
                       "Website | Filmography",
                       "Individual or company?",
                       "Individual",
                       "Company",
                       "Do you want to stay in touch?",
                       "Choose option...",
                       "No updates please.",
                       "Please keep me updated.",
                       "I have read and agree to the",
                       "/privacy/",
                       "privacy policy",
                       "Publicly sign statement");

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FREGISTER, $html, $result);
      }

      return $result;
    }

    protected static function register_post($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = register($_POST, $error);

        // generate HTML source code
        $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                       "  <p>".NL,
                       FM4FPlugin::class);

        if ($output) {
          $html .= tfhtml("    <b>%s</b><br>".NL.
                          "    %s<br>".NL.
                          "    %s".NL,
                          FM4FPlugin::class,
                          "Thank you for signing the statement.",
                          "We will send you an e-mail with further instructions.",
                          "Please check your spam folder, just in case.");
        } else {
          // generate HTML source code
          $html .= tfhtml("    <b>%s%s</b><br>".NL,
                          FM4FPlugin::class,
                          "Unfortunately, an error has occured",
                          (array_key_exists(ERROR_OUTPUT, $error)) ? ":" : ".");

          if (array_key_exists(ERROR_OUTPUT, $error)) {
            $html .= tfhtml("    %s<br>".NL,
                            FM4FPlugin::class,
                            $error[ERROR_OUTPUT]);
          }

          $html .= tfhtml("    %s <a href=\"%s\">%s</a>.".NL,
                          FM4FPlugin::class,
                          "Please try again later or",
                          "/contact/",
                          "contact us");

          if (array_key_exists(ERROR_ID, $error)) {
            $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                            FM4FPlugin::class,
                            "Please provide the following error id when contacting us about this issue:",
                            $error[ERROR_ID]);
          }
        }

        $html .= tfhtml("  </p>".NL.
                        "</div>".NL.
                        "<script src=\"%s\"></script>",
                        FM4FPlugin::class,
                        path2uri(__DIR__."/js/norepost.js"));

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FREGISTER, $html, $result);
      }

      return $result;
    }

    protected static function send_get($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = static::get_subscribed($error);

        if ($output) {
          $countries = [];
          if (is_array($output)) {
            foreach ($output as $output_item) {
              if (!array_key_exists(strtolower(trim($output_item[MAIL_COUNTRY])), $countries)) {
                $countries[strtolower(trim($output_item[MAIL_COUNTRY]))] = trim($output_item[MAIL_COUNTRY]);
              }
            }
            ksort($countries);
          }

          if (0 < count($countries)) {
            // generate HTML source code
            $html = tfhtml("<form class=\"text-center\" id=\"send_form\" action=\"%s\" method=\"post\">".NL.
                           "  <!-- Subject -->".NL.
                           "  <label class=\"mb-1\">%s</label>".NL.
                           "  <select name=\"country\" class=\"browser-default custom-select mb-4\" required>".NL.
                           "    <option value=\"\" disabled selected>%s</option>".NL.
                           "    <option value=\"\">%s</option>".NL,
                           FM4FPlugin::class,
                           value(Main::class, URI),
                           "Please select a subscriber country:",
                           "Choose option...",
                           "ALL COUNTRIES");

            foreach ($countries as $key => $value) {
              $html .= tfhtml("    <option value=\"%s\">%s</option>".NL,
                              FM4FPlugin::class,
                              $key,
                              $value);
            }

            $html .= tfhtml("  </select>".NL.
                            "  <!-- Subject -->".NL.
                            "  <input type=\"text\" name=\"subject\" maxlength=\"256\" placeholder=\"%s\" value=\"%s\" class=\"form-control mb-4\" required>".NL.
                            "  <!-- Message -->".NL.
                            "  <div class=\"form-group\">".NL.
                            "    <textarea name=\"message\" rows=\"5\" placeholder=\"%s\" class=\"form-control mb-4\" required>%s</textarea>".NL.
                            "  </div>".NL.
                            "  <!-- Password -->".NL.
                            "  <input type=\"password\" name=\"password\" maxlength=\"256\" placeholder=\"%s\" class=\"form-control mb-4\" required>".NL.
                            "  <!-- Send button -->".NL.
                            "  <button class=\"btn btn-info btn-block mb-1\" id=\"send_button\" type=\"submit\">%s</button>".NL.
                            "</form>",
                            FM4FPlugin::class,
                            "Subject*",
                            value(Plugins::class, "NEWSLETTER_MAIL_SUBJECT"),
                            "Message*",
                            value(Plugins::class, "NEWSLETTER_MAIL_BODY"),
                            "Password*",
                            "Send newsletter");
          } else {
            $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                           "  <p>%s</p>".NL.
                           "</div>",
                           FM4FPlugin::class,
                           "There are currently no verified subscribers of the newsletter.");
          }
        } else {
          // generate HTML source code
          $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                         "  <p>".NL.
                         "    <b>%s%s</b><br>".NL,
                         FM4FPlugin::class,
                         "Unfortunately, an error has occured",
                         (array_key_exists(ERROR_OUTPUT, $error)) ? ":" : ".");

          if (array_key_exists(ERROR_OUTPUT, $error)) {
            $html .= tfhtml("    %s<br>".NL,
                            FM4FPlugin::class,
                            $error[ERROR_OUTPUT]);
          }

          $html .= tfhtml("    %s <a href=\"%s\">%s</a>.".NL,
                          FM4FPlugin::class,
                          "Please try again later or",
                          "/contact/",
                          "contact us");

          if (array_key_exists(ERROR_ID, $error)) {
            $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                            FM4FPlugin::class,
                            "Please provide the following error id when contacting us about this issue:",
                            $error[ERROR_ID]);
          }

          $html .= tfhtml("  </p>".NL.
                          "</div>",
                          FM4FPlugin::class);
        }

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FSEND, $html, $result);
      }

      return $result;
    }

    protected static function send_post($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = send_newsletter($_POST, $error);

        // generate HTML source code
        $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                       "  <p>".NL,
                       FM4FPlugin::class);

        if ($output) {
          $html .= tfhtml("    <b>%s</b><br>".NL.
                          "    %s".NL,
                          FM4FPlugin::class,
                          "The newsletter has been sent.",
                          "At the moment there is nothing more to do for you.");
        } else {
          // generate HTML source code
          $html .= tfhtml("    <b>%s%s</b><br>".NL,
                          FM4FPlugin::class,
                          "Unfortunately, an error has occured",
                          (array_key_exists(ERROR_OUTPUT, $error)) ? ":" : ".");

          if (array_key_exists(ERROR_OUTPUT, $error)) {
            $html .= tfhtml("    %s<br>".NL,
                            FM4FPlugin::class,
                            $error[ERROR_OUTPUT]);
          }

          $html .= tfhtml("    %s <a href=\"%s\">%s</a>.".NL,
                          FM4FPlugin::class,
                          "Please try again later or",
                          "/contact/",
                          "contact us");

          if (array_key_exists(ERROR_ID, $error)) {
            $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                            FM4FPlugin::class,
                            "Please provide the following error id when contacting us about this issue:",
                            $error[ERROR_ID]);
          }
        }

        $html .= tfhtml("  </p>".NL.
                        "</div>".NL.
                        "<script src=\"%s\"></script>",
                        FM4FPlugin::class,
                        path2uri(__DIR__."/js/norepost.js"));

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FSEND, $html, $result);
      }

      return $result;
    }

    protected static function signatures($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = static::get_verified($error);

        $html = "";
        if ($output) {
          if (is_array($output)) {
            $countries = [];
            foreach ($output as $output_item) {
              if (!array_key_exists($output_item[MAIL_COUNTRY], $countries)) {
                $countries[$output_item[MAIL_COUNTRY]] = [];
              }
              $countries[$output_item[MAIL_COUNTRY]][] = $output_item;
            }
            ksort($countries);

            $iseven = false;
            foreach ($countries as $country => $signatures) {
              $iseven = (!$iseven);

              $html .= tfhtml("<section class=\"page-section %s\">".NL.
                              "  <div class=\"container\">".NL.
                              "    <h2 class=\"text-white-50\">%s</h2>".NL.
                              "    <p class=\"text-body\">".NL,
                              FM4FPlugin::class,
                              ($iseven) ? "bg-dark text-left" : "bg-primary text-right",
                              $country);

              foreach ($signatures as $signature) {
                if ($signature[MAIL_ISCOMPANY]) {
                  $html .= tfhtml("      <span class=\"text-white-75 fa fa-briefcase\"></span> ",
                                  FM4FPlugin::class);
                }

                if (empty($signature[MAIL_WEBSITE])) {
                  $html .= tfhtml("<span class=\"text-white\">%s</span> ",
                                  FM4FPlugin::class,
                                  $signature[MAIL_NAME]);
                } else {
                  $html .= tfhtml("<a target=\"_blank\" rel=\"noopener noreferrer\" href=\"%s\" class=\"text-white\">%s</a> ",
                                  FM4FPlugin::class,
                                  $signature[MAIL_WEBSITE],
                                  $signature[MAIL_NAME]);
                }

                $html .= tfhtml("<span class=\"text-white-50\" >(%s)</span> · ".NL,
                                FM4FPlugin::class,
                                $signature[MAIL_JOB]);
              }

              $html .= tfhtml("    </p>".NL.
                              "  </div>".NL.
                              "</section>".NL,
                              FM4FPlugin::class);
            }
          }
        }

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FSIGNATURES, $html, $result);
      }

      return $result;
    }

    protected static function signaturescount($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = static::get_verified($error);

        $count = 0;
        if ($output) {
          if (is_array($output)) {
            $count = count($output);
          }
        }
        $count = strval($count);

        // replace shortcode with generated count
        $result = str_ireplace(static::FM4FSIGNATURESCOUNT, $count, $result);
      }

      return $result;
    }

    protected static function signaturesdate($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = static::get_verified($error);

        $date = "none";
        if ($output) {
          if (is_array($output)) {
            $date = date("j F Y", hexdec(substr(end($output)[MAIL_UID], 0, 8)));
          }
        }

        // replace shortcode with generated date
        $result = str_ireplace(static::FM4FSIGNATURESDATE, $date, $result);
      }

      return $result;
    }


    protected static function verify_get($content) {
      $result = $content;

      if (is_string($result)) {
        // check if a link was used
        if (array_key_exists("uid", $_GET) && (array_key_exists("admin", $_GET) || array_key_exists("user", $_GET))) {
          $error  = [];  // has to be defined as an array
          $output = preview_verify($_GET, $error);

          if ($output) {
            // check if this is an admin verify or user verify link
            $isadmin = (array_key_exists("uid", $_GET) && array_key_exists("admin", $_GET) && !array_key_exists("user", $_GET));

            // generate HTML source code
            $html = tfhtml("<p class=\"text-white-85 mb-4\">%s</p>".NL.
                           "<form class=\"text-center\" id=\"verify_form\" action=\"%s\" method=\"post\">".NL.
                           "  <!-- Name -->".NL.
                           "  <input type=\"text\" name=\"name\" maxlength=\"256\" placeholder=\"%s\" value=\"%s\" class=\"form-control mb-4\" required>".NL.
                           "  <!-- Email -->".NL.
                           "  <input type=\"email\" name=\"mail\" maxlength=\"256\" placeholder=\"%s\" value=\"%s\" class=\"form-control mb-4\" required %s>".NL.
                           "  <!-- Job -->".NL.
                           "  <input type=\"text\" name=\"job\" maxlength=\"256\" placeholder=\"%s\" value=\"%s\" class=\"form-control mb-4\" required>".NL.
                           "  <!-- Country -->".NL.
                           "  <input type=\"text\" name=\"country\" maxlength=\"256\" placeholder=\"%s\" value=\"%s\" class=\"form-control mb-4\" required>".NL.
                           "  <!-- City -->".NL.
                           "  <input type=\"text\" name=\"city\" maxlength=\"256\" placeholder=\"%s\" value=\"%s\" class=\"form-control mb-4\">".NL.
                           "  <!-- Website -->".NL.
                           "  <input type=\"link\" name=\"website\" maxlength=\"256\" placeholder=\"%s\" value=\"%s\" class=\"form-control mb-4\">".NL.
                           "  <!-- individual or a company -->".NL.
                           "  <select name=\"iscompany\" class=\"browser-default custom-select mb-4\" required>".NL.
                           "    <option value=\"0\" %s>%s</option>".NL.
                           "    <option value=\"1\" %s>%s</option>".NL.
                           "  </select>".NL.
                           "  <!-- Newsletter -->".NL.
                           "  <label class=\"mb-1\">%s</label>".NL.
                           "  <select name=\"newsletter\" class=\"browser-default custom-select mb-4\" required %s>".NL.
                           "    <option value=\"0\" %s>%s</option>".NL.
                           "    <option value=\"1\" %s>%s</option>".NL.
                           "  </select>".NL.
                           "  <!-- Send button -->".NL.
                           "  <button class=\"btn btn-info btn-block mb-1\" id=\"verify_button\" type=\"submit\">%s</button>".NL.
                           "</form>",
                           FM4FPlugin::class,
                           ($isadmin) ? "Please verify the following signature:" : "You entered the following data to sign our statement:",
                           value(Main::class, URI).querystring(),
                           "Full name or company name*",
                           $output[MAIL_NAME],
                           "Email address*",
                           $output[MAIL_MAIL],
                           ($isadmin) ? "" : "disabled readonly",
                           "Job title or company field*",
                           $output[MAIL_JOB],
                           "Country*",
                           $output[MAIL_COUNTRY],
                           "City (not entered)",
                           $output[MAIL_CITY],
                           "Website | Filmography (not entered)",
                           $output[MAIL_WEBSITE],
                           ($output[MAIL_ISCOMPANY]) ? "" : "selected",
                           "Individual",
                           ($output[MAIL_ISCOMPANY]) ? "selected" : "",
                           "Company",
                           "Do you want to stay in touch?",
                           ($isadmin) ? "disabled readonly" : "",
                           ($output[MAIL_NEWSLETTER]) ? "" : "selected",
                           "No updates please.",
                           ($output[MAIL_NEWSLETTER]) ? "selected" : "",
                           "Please keep me updated.",
                           ($isadmin) ? "Authorize signature" : "Verify your signature");
          } else {
            // generate HTML source code
            $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                           "  <p>".NL.
                           "    <b>%s</b><br>".NL.
                           "    %s <a href=\"%s\">%s</a>.".NL,
                           FM4FPlugin::class,
                           "The verification link you used is invalid.",
                           "Please try again later or",
                           "/contact/",
                           "contact us");

            if (array_key_exists(ERROR_ID, $error)) {
              $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                              FM4FPlugin::class,
                              "Please provide the following error id when contacting us about this issue:",
                              $error[ERROR_ID]);
            }

            $html .= fhtml("  </p>".NL.
                           "</div>");
          }
        } else {
          // generate HTML source code
          $html = tfhtml("<p class=\"text-white-85 mb-4\">%s</p>".NL.
                         "<form class=\"text-center\" id=\"verify_form\" action=\"%s\" method=\"post\">".NL.
                         "  <input class=\"form-control mb-4\" type=\"email\" name=\"newmail\" required maxlength=\"256\" placeholder=\"%s\">".NL.
                         "  <button class=\"btn btn-info btn-block mb-3\" id=\"verify_button\" type=\"submit\">%s</button>".NL.
                         "  <p class=\"text-white-85 mb-0 font-weight-light\"><b>%s</b> %s</p>".NL.
                         "</form>",
                         FM4FPlugin::class,
                         "Request a new verification link:",
                         value(Main::class, URI),
                         "Email address*",
                         "Request a new link",
                         "Please note:",
                         "You can only verify your signature if you have signed our statement and have not yet finished the verification process.");
        }

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FVERIFY, $html, $result);
      }

      return $result;
    }

    protected static function verify_post($content) {
      $result = $content;

      if (is_string($result)) {
        $error  = [];  // has to be defined as an array
        $output = verify(array_merge($_GET, $_POST), $error);

        // generate HTML source code
        $html = tfhtml("<div class=\"text-center border border-light p-5\">".NL.
                       "  <p>".NL,
                       FM4FPlugin::class);

        if ($output) {
          // check if a link was used
          if (array_key_exists("uid", $_GET) && (array_key_exists("admin", $_GET) || array_key_exists("user", $_GET))) {
            // check if this is an admin verify or user verify link
            $isadmin = (array_key_exists("uid", $_GET) && array_key_exists("admin", $_GET) && !array_key_exists("user", $_GET));

            if ($isadmin) {
              $html .= tfhtml("    <b>%s</b><br>".NL.
                              "    %s".NL,
                              FM4FPlugin::class,
                              "Signature is online now.",
                              "At the moment there is nothing more to do for you.");
            } else {
              $html .= tfhtml("    <b>%s</b><br>".NL.
                              "    %s<br>".NL.
                              "    %s <a href=\"%s\">%s</a>!".NL,
                              FM4FPlugin::class,
                              "Thank you for verifying your signature.",
                              "We will review your signature and inform you as soon as it is validated.",
                              "In the meantime, please take a look at",
                              "/participate/",
                              "what else you can do");
            }
          } else {
            $html .= tfhtml("    <b>%s</b><br>".NL.
                            "    %s<br>".NL.
                            "    %s".NL,
                            FM4FPlugin::class,
                            "Thank you for verifying your signature.",
                            "We will send you an e-mail with further instructions.",
                            "Please check your spam folder, just in case.");
          }
        } else {
          // generate HTML source code
          $html .= tfhtml("    <b>%s%s</b><br>".NL,
                          FM4FPlugin::class,
                          "Unfortunately, an error has occured",
                          (array_key_exists(ERROR_OUTPUT, $error)) ? ":" : ".");

          if (array_key_exists(ERROR_OUTPUT, $error)) {
            $html .= tfhtml("    %s<br>".NL,
                            FM4FPlugin::class,
                            $error[ERROR_OUTPUT]);
          }

          $html .= tfhtml("    %s <a href=\"%s\">%s</a>.".NL,
                          FM4FPlugin::class,
                          "Please try again later or",
                          "/contact/",
                          "contact us");

          if (array_key_exists(ERROR_ID, $error)) {
            $html .= tfhtml("    <br>%s <span style=\"overflow-wrap: break-word;\">%s</span>".NL,
                            FM4FPlugin::class,
                            "Please provide the following error id when contacting us about this issue:",
                            $error[ERROR_ID]);
          }
        }

        $html .= tfhtml("  </p>".NL.
                        "</div>".NL.
                        "<script src=\"%s\"></script>",
                        FM4FPlugin::class,
                        path2uri(__DIR__."/js/norepost.js"));

        // replace shortcode with generated HTML
        $result = str_ireplace(static::FM4FVERIFY, $html, $result);
      }

      return $result;
    }

    protected static function videos($content) {
      $result = $content;

      if (is_string($result)) {
        // replace shortcode with videos output
        $result = preg_replace_callback(static::FM4FVIDEOS,
                                        function ($matches) { return static::videos_callback($matches); },
                                        $result);
      }

      return $result;
    }

    protected static function videos_callback($matches) {
      $result = "";

      if (isset($matches[static::VIDEOS])) {
        $videos = null;
        if (is_array(value(Plugins::class, static::VIDEOS)) &&
            array_key_exists($matches[static::VIDEOS], value(Plugins::class, static::VIDEOS))) {
          $videos = value(Plugins::class, static::VIDEOS)[$matches[static::VIDEOS]];
        }

        if (is_array($videos)) {
          foreach ($videos as $video) {
            if (array_key_exists("category", $video) && array_key_exists("hoster", $video) &&
                array_key_exists("language", $video) && array_key_exists("name", $video) &&
                array_key_exists("thumb", $video) && array_key_exists("url", $video)) {
              $result .= tfhtml(NL.
                                "<div class=\"col-lg-4 col-sm-6\">".NL.
                                "  <a class=\"video_grid-box\" href=\"%s\">".NL.
                                "    <img class=\"img-fluid\" src=\"%s\" alt=\"%s %s\">".NL.
                                "    <div class=\"video_grid-box-caption\">".NL.
                                "      <div class=\"video-category text-white-50\">%s</div>".NL.
                                "      <div class=\"video-name\">%s</div>".NL.
                                "      <div class=\"video-info\">[%s]</div>".NL.
                                "      <div class=\"video-info\">(%s)</div>".NL.
                                "    </div>".NL.
                                "  </a>".NL.
                                "</div>".NL,
                                FM4FPlugin::class,
                                $video["url"],
                                $video["thumb"],
                                "Preview image for Video:",
                                $video["name"],
                                $video["category"],
                                $video["name"],
                                $video["language"],
                                $video["hoster"]);
            }
          }
        }
      }

      return $result;
    }

    // PLUGIN FUNCTIONS

    public static function disable_system_handlers($argument) {
      $result = preparecontent($argument, null, [Plugins::ENTITY]);

      if (null !== $result) {
        // make sure that we only handle arrays
        if ($result instanceof Content) {
          $result = [$result];
        }

        $disabled_handlers = [FeedHandler::class,
                              SearchHandler::class];

        // iterate through the handlers and unset unsupported ones
        foreach ($result as $key => $value) {
          if (in_array(value($value, Plugins::ENTITY), $disabled_handlers)) {
            unset($result[$key]);
          }
        }
      }

      return $result;
    }

    public static function shortcodes($content) {
      $result = $content;

      // preset plugin configuration
      static::configure();

      if ($result instanceof Content) {
        if ($result->isset(CONTENT)) {
          $result->set(CONTENT, static::shortcodes_helper(value($result, CONTENT)));
        }

        // additionally support fm4fsignaturesdate in DATE
        if ($result->isset(DATE)) {
          if (false !== strpos(value($result, DATE), static::FM4FSIGNATURESDATE)) {
            $result->set(DATE, static::signaturesdate(value($result, DATE)));
          }
        }
      } else {
        if (is_array($result)) {
          // iterate through all content items
          foreach ($result as $result_item) {
            if ($result_item instanceof Content) {
              if ($result_item->isset(CONTENT)) {
                $result_item->set(CONTENT, static::shortcodes_helper(value($result_item, CONTENT)));
              }

              // additionally support fm4fsignaturesdate in DATE
              if ($result_item->isset(DATE)) {
                if (false !== strpos(value($result_item, DATE), static::FM4FSIGNATURESDATE)) {
                  $result_item->set(DATE, static::signaturesdate(value($result_item, DATE)));
                }
              }
            }
          }
        }
      }

      return $result;
    }

    protected static function shortcodes_helper($content) {
      $result = $content;

      if (is_string($result)) {
        if (false !== strpos($result, static::FM4FCONTACT)) {
          switch (strtoupper(value(Main::class, METHOD))) {
            case GET:
              $result = static::contact_get($result);
              break;

            case POST:
              $result = static::contact_post($result);
              break;
          }
        }

        if (false !== strpos($result, static::FM4FNEWSLETTER)) {
          switch (strtoupper(value(Main::class, METHOD))) {
            case GET:
              $result = static::newsletter_get($result);
              break;

            case POST:
              $result = static::newsletter_post($result);
              break;
          }
        }

        if (false !== strpos($result, static::FM4FREGISTER)) {
          switch (strtoupper(value(Main::class, METHOD))) {
            case GET:
              $result = static::register_get($result);
              break;

            case POST:
              $result = static::register_post($result);
              break;
          }
        }

        if (false !== strpos($result, static::FM4FSEND)) {
          switch (strtoupper(value(Main::class, METHOD))) {
            case GET:
              $result = static::send_get($result);
              break;

            case POST:
              $result = static::send_post($result);
              break;
          }
        }

        if (false !== strpos($result, static::FM4FSIGNATURES)) {
          $result = static::signatures($result);
        }

        if (false !== strpos($result, static::FM4FSIGNATURESCOUNT)) {
          $result = static::signaturescount($result);
        }

        if (false !== strpos($result, static::FM4FSIGNATURESDATE)) {
          $result = static::signaturesdate($result);
        }

        if (false !== strpos($result, static::FM4FVERIFY)) {
          switch (strtoupper(value(Main::class, METHOD))) {
            case GET:
              $result = static::verify_get($result);
              break;

            case POST:
              $result = static::verify_post($result);
              break;
          }
        }

        if (1 === preg_match(static::FM4FVIDEOS, $result)) {
          $result = static::videos($result);
        }
      }

      return $result;
    }

  }

  // include fff-signup
  require_once(__DIR__."/lib/consts.php");
  require_once(__DIR__."/lib/functs.php");

  // register plugin
  Plugins::register(FM4FPlugin::class, "disable_system_handlers", FILTER_HANDLERS);
  Plugins::register(FM4FPlugin::class, "shortcodes",              FILTER_CONTENT);

  // register translation
  Translate::register(__DIR__.DS."lang".DS, FM4FPlugin::class);
