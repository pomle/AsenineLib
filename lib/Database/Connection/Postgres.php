<?
/**
 * Represents a Connection to a Postgres database.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database\Connection;

class Postgres extends \Asenine\Database\Connection
{
	const TYPE_TRUE = 'true';
	const TYPE_FALSE = 'false';
	const TYPE_TIMESTAMP = "'Y-m-d H:i:s'";
}