-- Plan: documentos Stripe (homólogo a Facturas: stripe_doc / stripe_url / stripe_pdf).
ALTER TABLE `plan`
  ADD COLUMN IF NOT EXISTS `stripe_doc` text DEFAULT NULL AFTER `stripeName`,
  ADD COLUMN IF NOT EXISTS `stripe_url` text DEFAULT NULL AFTER `stripe_doc`,
  ADD COLUMN IF NOT EXISTS `stripe_pdf` text DEFAULT NULL AFTER `stripe_url`;
