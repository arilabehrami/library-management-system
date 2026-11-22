<?php

namespace Application\Entity;

enum LoanStatus: string{
    case Active = 'active';
    case Overdue = 'overdue';
    case AwaitingPayment = 'awaiting_payment';
    case Closed = 'closed';
}