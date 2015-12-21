<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Assert\Assertion;
use Symfony\Component\Filesystem\Filesystem;
use Creads\Api2Symfony\Converter\RamlConverter;
use Creads\Api2Symfony\Dumper\SymfonyDumper;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    const GENERATED_DIR     = 'features/generated';
    const SPECIFICATION_DIR = 'features/specification';

    /**
     * @var RamlConverter
     */
    private $converter;

    /**
     * @var SymfonyDumper
     */
    private $dumper;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->converter = new RamlConverter();

        $this->dumper = new SymfonyDumper();
    }

    /** @BeforeSuite */
    public static function setup(BeforeSuiteScope $event)
    {
        $fs = new Filesystem();

        $fs->remove(self::GENERATED_DIR);

        $fs->mkdir(self::GENERATED_DIR);
    }

    /** @AfterSuite */
    public static function teardown(AfterSuiteScope $event)
    {
        $fs = new Filesystem();

        $fs->remove(self::GENERATED_DIR);
    }

    /**
     * @Given I have :filename
     */
    public function iHave($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @Given I want to use :namespace as namespace
     */
    public function iWantToUseAsNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @When I generate
     */
    public function iGenerate()
    {
        //get controller models from specification
        $controllers = $this->converter->convert(self::SPECIFICATION_DIR . '/' . $this->filename, $this->namespace);

        //dump each controller into current directory
        foreach($controllers as $controller) {
          $this->dumper->dump($controller, self::GENERATED_DIR);
        }
    }

    /**
     * @Then I should get :pathfile file generated with given content:
     */
    public function iShouldGetFileGeneratedWithGivenContent($pathfile, PyStringNode $content)
    {
        $pathfile = self::GENERATED_DIR . '/' . $pathfile;

        Assertion::file($pathfile);
        Assertion::readable($pathfile);
        Assertion::same(file_get_contents($pathfile), (string)$content);
    }
}
