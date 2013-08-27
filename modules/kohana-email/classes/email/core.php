<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Kohana Email abstraction module for Swiftmailer
 *
 * @uses       Swiftmailer (v4.2.1)
 * @package    Core
 * @author     Kohana Team
 * @author     Lieuwe Jan Eilander
 * @copyright  (c) 2007-2010 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Email_Core {

  /**
   * @var  Swiftmailer  Holds Swiftmailer instance
   */
  protected static $_mail;

  /**
   * Creates a SwiftMailer instance.
   *
   * @param   string  DSN connection string
   * @return  object  Swift object
   */
  public static function connect($config = NULL)
  {
    // Load default configuration
    ($config === NULL) AND $config = Kohana::$config->load('email');

    switch ($config['driver'])
    {
      case 'smtp':
        // Set port
        $port = empty($config['options']['port']) ? 25 : (int) $config['options']['port'];

        // Create SMTP Transport
        $transport = Swift_SmtpTransport::newInstance($config['options']['hostname'], $port);

        if ( ! empty($config['options']['encryption']))
        {
          // Set encryption
          $transport->setEncryption($config['options']['encryption']);
        }

        // Do authentication, if part of the DSN
        empty($config['options']['username']) OR $transport->setUsername($config['options']['username']);
        empty($config['options']['password']) OR $transport->setPassword($config['options']['password']);

        // Set the timeout to 5 seconds
        $transport->setTimeout(empty($config['options']['timeout']) ? 5 : (int) $config['options']['timeout']);
      break;
      case 'sendmail':
        // Create a sendmail connection
        $transport = Swift_SendmailTransport::newInstance(empty($config['options']) ? "/usr/sbin/sendmail -bs" : $config['options']);
      break;
      default:
        // Use the native connection
        $transport = Swift_MailTransport::newInstance();
      break;
    }

    // Create the SwiftMailer instance
    return self::$_mail = Swift_Mailer::newInstance($transport);
  }

  /**
   * Send an email message.
   *
   * @param   mixed         Recipient email (and name), or an array of To, Cc, Bcc names
   * @param   mixed         Sender email (and name)
   * @param   string        Message subject
   * @param   string        Message body
   * @param   boolean       Send email as HTML
   * @return  integer       Number of emails sent
   * @throws  Http_Exception_408  If connecting to the mailserver is timed-out
   */
  public static function send($to, $from, $subject, $body, $html = FALSE)
  {
    // Connect to SwiftMailer
    (self::$_mail === NULL) AND self::connect();

    // Determine the message type
    $html = ($html === TRUE) ? 'text/html' : 'text/plain';

    // Create the message
    $message = Swift_Message::newInstance($subject, $body, $html, 'utf-8');

    if (is_string($to))
    {
      // Single recipient
      $message->setTo($to);
    }
    elseif (is_array($to))
    {
      if (isset($to[0]) AND isset($to[1]))
      {
        // Create To: address set
        $to = array('to' => $to);
      }

      foreach ($to as $method => $set)
      {
        if ( ! in_array($method, array('to', 'cc', 'bcc'), TRUE))
        {
          // Use To: by default
          $method = 'to';
        }

        // Create method name
        $method = 'add'.ucfirst($method);

        if (is_array($set))
        {
          // Add a recipient with name
          $message->$method($set[0], $set[1]);
        }
        else
        {
          // Add a recipient without name
          $message->$method($set);
        }
      }
    }

    if (is_string($from))
    {
      // From without a name
      $message->setFrom($from);
    }
    elseif (is_array($from))
    {
      // From with a name
      $message->setFrom($from[0], $from[1]);
    }

    try
    {
      return self::$_mail->send($message);
    }
    catch (Swift_SwiftException $e)
    {
      // Throw Kohana Http Exception
      throw new Http_Exception_408('Connecting to mailserver timed out: :message', array(
        ':message' => $e->getMessage()
      ));
    }
  }

} // End email
