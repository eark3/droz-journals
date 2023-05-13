<doi_batch xmlns="http://www.crossref.org/schema/4.3.6" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1" xmlns:ai="http://www.crossref.org/AccessIndicators.xsd" version="4.3.6" xsi:schemaLocation="http://www.crossref.org/schema/4.3.6 https://www.crossref.org/schemas/crossref4.3.6.xsd">
  <head>
    <doi_batch_id><?php echo DROZ_DOI_PREFIX; ?><?php echo $issue['short']; ?>@<?php echo date('Ymd.His'); ?></doi_batch_id>
    <timestamp><?php echo time(); ?></timestamp>
    <depositor>
      <depositor_name><?php echo CROSSREF_DEPOSITOR_NAME; ?></depositor_name>
      <email_address><?php echo CROSSREF_DEPOSITOR_EMAIL; ?></email_address>
    </depositor>
    <registrant><?php echo CROSSREF_REGISTRANT; ?></registrant>
  </head>
  <body>
<?php foreach ($articles as $article) { ?>
    <journal>
      <journal_metadata>
        <full_title><?php echo $journal['title']; ?></full_title>
        <abbrev_title><?php echo $journal['abbrev']; ?></abbrev_title>
<?php if (!empty($journal['issn']['online'])) { ?>
        <issn media_type="electronic"><?php echo $journal['issn']['online']; ?></issn>
<?php } ?>
<?php if (!empty($journal['issn']['print'])) { ?>
        <issn media_type="print"><?php echo $journal['issn']['print']; ?></issn>
<?php } ?>
      </journal_metadata>
      <journal_issue>
        <publication_date media_type="online">
          <month><?php echo $issue['date']['month']; ?></month>
          <day><?php echo $issue['date']['day']; ?></day>
          <year><?php echo $issue['date']['year']; ?></year>
        </publication_date>
        <journal_volume>
          <volume><?php echo $issue['volume']; ?></volume>
        </journal_volume>
<?php if ($issue['number'] ?? false) { ?>
        <issue><?php echo $issue['number']; ?></issue>
<?php } ?>
      </journal_issue>
      <journal_article xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1" xmlns:ai="http://www.crossref.org/AccessIndicators.xsd" publication_type="full_text" metadata_distribution_opts="any">
        <titles>
          <title><?php echo $article['title']; ?></title>
        </titles>
<?php if (!empty($article['authors'])) { ?>
        <contributors>
<?php   foreach ($article['authors'] as $index => $author) { ?>
          <person_name contributor_role="author" sequence="first">
            <given_name><?php echo $author['first']; ?></given_name>
            <surname><?php echo $author['last']; ?></surname>
          </person_name>
<?php   } ?>
        </contributors>
<?php } ?>
<?php if (isset($article['abstract'])) { ?>
        <jats:abstract xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1">
          <jats:p><?php echo $article['abstract']; ?></jats:p>
        </jats:abstract>
<?php } ?>
        <publication_date media_type="online">
          <month><?php echo $issue['date']['month']; ?></month>
          <day><?php echo $issue['date']['day']; ?></day>
          <year><?php echo $issue['date']['year']; ?></year>
        </publication_date>
        <pages>
          <first_page><?php echo $article['start']; ?></first_page>
          <last_page><?php echo $article['end']; ?></last_page>
        </pages>
        <ai:program xmlns:ai="http://www.crossref.org/AccessIndicators.xsd" name="AccessIndicators">
          <ai:license_ref><?php echo $baseURL; ?>/license</ai:license_ref>
        </ai:program>
        <doi_data>
          <doi><?php echo $article['doi']; ?></doi>
          <resource><?php echo $baseURL; ?>/article/view/<?php echo $article['short']; ?></resource>
          <collection property="crawler-based">
            <item crawler="iParadigms">
              <resource><?php echo $baseURL; ?>/article/download/<?php echo $article['short']; ?>/pdf</resource>
            </item>
          </collection>
          <collection property="text-mining">
            <item>
              <resource mime_type="application/pdf"><?php echo $baseURL; ?>/article/download/<?php echo $article['short']; ?>/pdf</resource>
            </item>
            <item>
              <resource mime_type="text/html"><?php echo $baseURL; ?>/article/download/<?php echo $article['short']; ?>/html</resource>
            </item>
            <item>
              <resource>https://www.droz.org/product/<?php echo $issue['ean']; ?>/<?php echo $article['short']; ?></resource>
            </item>
          </collection>
        </doi_data>
      </journal_article>
    </journal>
<?php } ?>
  </body>
</doi_batch>
