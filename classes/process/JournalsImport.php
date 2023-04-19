<?php

class JournalsImport extends Import {
    
    public function contents($ean) {
        $contents = [];
        $issue = (new IssueEntity())->retrieveOne($ean);
        $journal = (new JournalEntity())->retrieveOne($issue->journal);
        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id]);
        $context = $journal->context;
        $date = $issue->date;
        foreach ($papers as $paper) {
            $authors = [];
            foreach ((new AuthorEntity())->retrieveAll(['paper' => $paper->id]) as $author) {
                $authors[] = Zord::collapse(JournalsUtils::name($author), false);
            }
            $authors = implode(' ', $authors);
            $short = JournalsUtils::short($journal->context, $issue, $paper);
            foreach (['html'/*,'pdf'*/] as $type) {
                $name = $paper->pages.'_'.$type;
                $file = JournalsUtils::path($journal->context, $issue->volume, $issue->number, $short, 'html');
                if (file_exists($file)) {
                    $content = Store::align(file_get_contents($file), $type, true);
                    $contents[] = [
                        'name'    => $name,
                        'short'   => $short,
                        'type'    => $type,
                        'date'    => $date,
                        'journal' => $context,
                        'authors' => $authors,
                        'content' => $content
                    ];
                }
            }
        }
        return $contents;
    }
    
}

?>