<?php

namespace Drupal\Tests\brandfolder\Functional;

use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality of the Brandfolder module.
 *
 * @group brandfolder
 */
class ConfigurationTest extends BrowserTestBase {



  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Module(s) for core functionality.
    'node',
    'views',

    // This custom module.
    'brandfolder',
  ];

  /**
   * Created three users, each will be assigned different permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser, $nonPrivilegedUser, $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Grant anonymous users the ability to view profiles.
    $anonymousUser = Role::load(Role::ANONYMOUS_ID);
    $this->grantPermissions($anonymousUser, ['access user profiles']);

    // Create admin user with valid permission.
    $this->privilegedUser = $this->drupalCreateUser(['administer brandfolder settings'], "privileged_user", FALSE);

    // Create admin user with invalid permission.
    $this->adminUser = $this->drupalCreateUser(['administer users'], "admin_user", TRUE);

    // Create regular user with invalid permission.
    $this->nonPrivilegedUser = $this->drupalCreateUser(['access content', 'access user profiles'], 'regular_user');

  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testUserAccess() {
    /**
     * Users with the 'administer brandfolder settings' permission should be
     * able to access the Branddfolder configuration settings.
    */
    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet(Url::fromRoute('brandfolder.brandfolder_settings_form'));
    $this->assertSession()->statusCodeEquals(200);

    /**
     * Admin users without the 'administer brandfolder settings' permission
     * should be able to access the Brandfolder configuration settings.
    */
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('brandfolder.brandfolder_settings_form'));
    $this->assertSession()->statusCodeEquals(200);

    /**
     * Regular Users without the 'administer brandfolder settings'
     * permission should not be able to access the Brandfolder configuration settings.
    */
    $this->drupalLogin($this->nonPrivilegedUser);
    $this->drupalGet(Url::fromRoute('brandfolder.brandfolder_settings_form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  public function testSubmitForm() {
    // Login as an admin and go to the config page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('brandfolder.brandfolder_settings_form'));
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the field exist.
    $config = $this->config('brandfolder.brandfoldersettings');
    $this->assertSession()->fieldValueEquals(
      'brandfolder_api_key',
      $config->get('brandfolder.brandfolder_api_key')
    );

    // Test form submission.
    $edit = [
      'brandfolder_api_key' => '123456789'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->statusCodeEquals(200);
    // Check the form loads and the API key was saved.
    $this->drupalGet(Url::fromRoute('brandfolder.brandfolder_settings_form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('edit-brandfolder-api-key', '123456789' );

    // Get this to a working state.
    // Check if value was stored in the configurations.
//    $config = $this->config('brandfolder.brandfoldersettings');
//    $api_key = \Drupal::config('brandfolder.brandfoldersettings')->get('brandfolder.brandfolder_api_key');
//    $this->assertEquals(
//      '123456789',
//      $api_key
//    );

  }

}