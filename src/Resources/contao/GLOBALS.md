# Contao's global variables

Contao uses the variable $GLOBALS to store runtime data.

## $GLOBALS['BE_MOD']

## $GLOBALS['TL_LANG']
*	Translated strings, mostly defined in `Resources/contao/languages` of some extension, either as XLF-file or PHP-script.
*   Can be manually loaded by `\System::loadLanguageFile(<range>)`, e.g. with range `tl_page`.
*	Many translations are an array with two items containing a short and a full version of the text.
### MSC
*	Captions for many general actions are defined in `$GLOBALS['TL_LANG']['MSC']`.
    ```xml
    <?xml version="1.0" ?><xliff version="1.1">
      <file datatype="php" original="src/Resources/contao/languages/en/default.php" source-language="en" target-language="de">
        <body>
          ...
          <trans-unit id="MSC.url.0">
            <source>Link target</source>
            <target>Link-Adresse</target>
          </trans-unit>
          <trans-unit id="MSC.url.1">
            <source>Please enter a web address (http://…), an e-mail address (mailto:…) or an insert tag.</source>
            <target>Geben Sie eine Web-Adresse (http://…), eine E-Mail-Adresse (mailto:…) oder ein Inserttag ein.</target>
          </trans-unit>
          ...
          <trans-unit id="MSC.decimalSeparator">
            <source>.</source>
            <target>,</target>
          </trans-unit>
          <trans-unit id="MSC.thousandsSeparator">
            <source>,</source>
            <target>.</target>
          </trans-unit>
          ...
        </body>
      </file>
    </xliff>
    ```
### MOD
*	Captions for backend modules are defined in `$GLOBALS['TL_LANG']['MOD']`.
    ```xml
    <?xml version="1.0" ?><xliff version="1.1">
      <file datatype="php" original="src/Resources/contao/languages/en/modules.php" source-language="en" target-language="de">
        <body>
          <trans-unit id="MOD.content">
            <source>Content</source>
            <target>Inhalte</target>
          </trans-unit>
          <trans-unit id="MOD.article.0">
            <source>Articles</source>
            <target>Artikel</target>
          </trans-unit>
          <trans-unit id="MOD.article.1">
            <source>Manage articles and content elements</source>
            <target>Artikel und Inhaltselemente verwalten</target>
          </trans-unit>
          ...
        </body>
      </file>
    </xliff>
    ```
### FMD
*	Captions for frontend modules are defined in `$GLOBALS['TL_LANG']['FMD']`.
### Tables, e.g. `tl_page`
*	Captions regarding the data management in the backend
    ```xml
    <?xml version="1.0" ?><xliff version="1.1">
      <file datatype="php" original="src/Resources/contao/languages/en/tl_page.php" source-language="en" target-language="de">
        <body>
          <trans-unit id="tl_page.title.0">
            <source>Page name</source>
            <target>Seitenname</target>
          </trans-unit>
          <trans-unit id="tl_page.title.1">
            <source>Please enter the page name.</source>
            <target>Bitte geben Sie den Namen der Seite ein.</target>
          </trans-unit>
          ...
        </body>
      </file>
    </xliff>
    ```

## $GLOBALS['TL_CSS']
*
	```php
	$GLOBALS['TL_CSS'][] = 'bundles/<extension>/style.css';
	```


## $GLOBALS['TL_JAVASCRIPT']

## $GLOBALS['TL_DCA']
*	"Data Container Arrays (DCAs) are used to store table meta data. Each DCA describes a particular table in terms of its configuration, its relations to other tables and its fields. The Contao core engine determines by this meta data how to list records, how to render back end forms and how to save data. The DCA files of all active module are loaded one after the other (starting with "backend" and "frontend" and then in alphabetical order), so that every module can override the existing configuration."
*   Can be manually loaded by `\Controller::loadDataContainer(<table-name>)`, e.g. with table-name `tl_page`.
*	See: https://docs.contao.org/books/api/dca/reference.html and https://docs.contao.org/books/api/dca/palettes.html

## $GLOBALS['TL_HOOKS']
*	See: https://docs.contao.org/books/api/extensions/hooks/
### getUserNavigation

## $GLOBALS['TL_AUTO_ITEM']

## $GLOBALS['TL_CRON']

## $GLOBALS['TL_NOINDEX_KEYS']

## $GLOBALS['TL_KEYWORDS']

## $GLOBALS['TL_LANGUAGE']

## $GLOBALS['TL_USERNAME']

## $GLOBALS['TL_PERMISSIONS']
