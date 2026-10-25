 <section id="about" class="about-area">
        <div class="container">
            <div class="row d-flex align-items-center">
                <div class="col-lg-12 col-md-12">
                    <div class="about-content">
                        <h2><?=@$details->pageTitle?></h2>
                        <h3> <?= @$details->pageText ?></h3>
                        <?= html_entity_decode(@$details->content); ?>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
 