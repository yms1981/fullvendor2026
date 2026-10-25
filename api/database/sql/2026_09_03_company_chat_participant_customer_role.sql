-- Ampliar role_type de participantes de chat para incluir customer.
ALTER TABLE `company_conversation_participants`
  MODIFY `role_type` enum('seller','warehouse','manager','customer') NOT NULL;
