<?php
  namespace rjdeliveryomaha\courierinvoice;

  use rjdeliveryomaha\courierinvoice\CommonFunctions;

  class Route extends CommonFunctions {
    protected $newTickets;
    protected $activeTicketSet = [];
    protected $onCallTicketSet = [];
    protected $contractTicketSet = [];
    protected $transferredTicketSet = [];
    protected $ticketSet = [];
    protected $singleLocation = [];
    protected $multiLocation = [];
    protected $cancelations = [];
    protected $processTransfer = false;
    protected $rescheduledRuns;
    protected $rescheduledRunsList = [];
    protected $cancelRoute = false;
    protected $driverID;
    protected $driverName;
    protected $today;
    protected $backstop;
    protected $dateObject;
    protected $testDate;
    protected $testDateObject;
    protected $add;
    protected $runList = [];
    protected $locations = [];
    protected $locationTest;
    protected $LastSeen;
    // List of drivers on file for transfer data list for drivers without dispatch authorization
    protected $transferList;
    private $tTest;

    public function __construct($options, $data=[])
    {
      try {
        parent::__construct($options, $data);
      } catch (Exception $e) {
        throw $e;
      }
      $this->driverID = $_SESSION['DriverID'] ?? $_SESSION['DispatchID'];
      $this->driverName = "{$_SESSION['FirstName']} {$_SESSION['LastName']}";
      $this->LastSeen = $_SESSION['LastSeen'];
      try {
        self::setTimezone();
      } catch (Exception $e) {
        $this->error = $e->getMessage();
        if ($this->enableLogging !== false) self::writeLoop();
        throw $e;
      }
      try {
        $this->dateObject = new \dateTime('NOW', $this->timezone);
      } catch (Exception $e) {
        $this->error = __function__ . ' Date Error Line ' . __line__ . ': ' . $e->getMessage();
        if ($this->enableLogging !== false) self::writeLoop();
        throw $e;
      }
      $this->today = $this->dateObject->format('Y-m-d');
      $temp = clone $this->dateObject;
      $temp->modify('- 7 days');
      $this->backstop = $temp->format('Y-m-d');
    }

    private function setLastSeen()
    {
      $lastSeenUpdateData['endPoint'] = (array_key_exists('driver_index', $_SESSION)) ? 'drivers' : 'dispatchers';
      $lastSeenUpdateData['method'] = 'PUT';
      $lastSeenUpdateData['primaryKey'] = (array_key_exists('driver_index', $_SESSION)) ?
        $_SESSION['driver_index'] : $_SESSION['dispatch_index'];

      $lastSeenUpdateData['payload'] = ['LastSeen'=>$this->today];
      if (!$lastSeenUpdate = self::createQuery($lastSeenUpdateData)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->error;
      }
      $lastSeenUpdateResult = self::callQuery($lastSeenUpdate);
      if ($lastSeenUpdateResult === false) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return false;
      }
      return $this->LastSeen = $_SESSION['LastSeen'] = $this->today;
    }

    public function onCallTickets()
    {
      $this->ticketSet = $ticketQueryData = [];
      $ticketQueryData['endPoint'] = 'tickets';
      $ticketQueryData['method'] = 'GET';
      $ticketQueryData['queryParams']['filter'] = [];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'NotForDispatch', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Contract', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'DispatchTimeStamp', 'Filter'=>'bt', 'Value'=>"{$this->backstop} 00:00:00,{$this->today} 23:59:59"],
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Charge', 'Filter'=>'bt', 'Value'=>'1,5'],
        ['Resource'=>'dTimeStamp', 'Filter'=>'is']
      ];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'NotForDispatch', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Contract', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'DispatchTimeStamp', 'Filter'=>'bt', 'Value'=>"{$this->backstop} 00:00:00,{$this->today} 23:59:59"],
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>6],
        ['Resource'=>'d2TimeStamp', 'Filter'=>'is']
      ];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'NotForDispatch', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Contract', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'DispatchTimeStamp', 'Filter'=>'bt', 'Value'=>"{$this->backstop} 00:00:00,{$this->today} 23:59:59"],
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>7],
        ['Resource'=>'dTimeStamp', 'Filter'=>'is'],
        ['Resource'=>'d2SigReq', 'Filter'=>'eq', 'Value'=>0]
      ];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'NotForDispatch', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Contract', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'DispatchTimeStamp', 'Filter'=>'bt', 'Value'=>"{$this->backstop} 00:00:00,{$this->today} 23:59:59"],
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>7],
        ['Resource'=>'d2TimeStamp', 'Filter'=>'is'],
        ['Resource'=>'d2SigReq', 'Filter'=>'eq', 'Value'=>1]
      ];
      if (!$ticketQuery = self::createQuery($ticketQueryData)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->error;
      }
      $this->onCallTicketSet = self::callQuery($ticketQuery);
      if ($this->onCallTicketSet === false) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
      }
      if (count($this->onCallTicketSet) === 0) {
        return '<p class="center result">No On Call Tickets On File</p>';
      }
      $returnData = '';
      for ($i = 0; $i < count($this->onCallTicketSet); $i++) {
        $ticket = self::createTicket($this->onCallTicketSet[$i]);
        if ($ticket === false) {
          $temp = $this->error;
          $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
          if ($this->enableLogging !== false) self::writeLoop();
          $returnData .= "<p class=\"center result\"><span class=\"error\">Error</span>: {$this->error}</p>";
        } else {
          $returnData .= $ticket->displaySingleTicket();
        }
      }
      return $returnData;
    }

    public function routeTickets()
    {
      $output = '';
      if (!self::buildRoute()) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return self::getError();
      }
      // Check for active contract tickets
      // Pull Round Trip ticket
      $ticketQueryData['endPoint'] = 'tickets';
      $ticketQueryData['method'] = 'GET';
      // Pull RoundTrip tickets
      $roundTripFilter = [
        ['Resource'=>'NotForDispatch', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Contract', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'DispatchTimeStamp', 'Filter'=>'bt', 'Value'=>"{$this->today} 00:00:00,{$this->today} 23:59:59"],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>6],
        ['Resource'=>'d2TimeStamp', 'Filter'=>'is'],
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>0]
      ];
      // Pull Routine tickets
      $routineFilter = [
        ['Resource'=>'NotForDispatch', 'Filter'=>'eq', 'Value'=>0],
        ['Resource'=>'Contract', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'DispatchTimeStamp', 'Filter'=>'bt', 'Value'=>"{$this->today} 00:00:00,{$this->today} 23:59:59"],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>5],
        ['Resource'=>'dTimeStamp', 'Filter'=>'is'],
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>0]
      ];

      $ticketQueryData['queryParams']['filter'] = [ $roundTripFilter, $routineFilter ];
      $ticketQueryData['queryParams']['order'] = [ 'pTimeStamp' ];

      if (!$ticketQuery = self::createQuery($ticketQueryData)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->error;
      }
      $this->activeTicketSet = self::callQuery($ticketQuery);
      if ($this->activeTicketSet === false) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
      }
      // Check for completed contract tickets
      if (empty($this->activeTicketSet)) {
        //  Check for completed contract tickets for today
        // Only queryParams['filter'] needs to be changed here
        $ticketQueryData['queryParams']['filter'] = [
          ['Resource'=>'NotForDispatch', 'Filter'=>'eq', 'Value'=>0],
          ['Resource'=>'Contract', 'Filter'=>'eq', 'Value'=>1],
          ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
          ['Resource'=>'Charge', 'Filter'=>'neq', 'Value'=>0],
          ['Resource'=>'DispatchTimeStamp', 'Filter'=>'bt', 'Value'=>"{$this->today} 00:00:00,{$this->today} 23:59:59"],
          ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>0]
        ];
        if (!$ticketQuery = self::createQuery($ticketQueryData)) {
          $temp = $this->error . "\n";
          $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
          if ($this->enableLogging !== false) self::writeLoop();
          return $this->error;
        }
        $tempTicketSet = self::callQuery($ticketQuery);
        if ($tempTicketSet === false) {
          $temp = $this->error . "\n";
          $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
          if ($this->enableLogging !== false) self::writeLoop();
          return "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
        }
        $state = (count($tempTicketSet) > 0) ? 'Complete' : 'Empty';
        return "<p class=\"center result\">{$this->dateObject->format('d M Y')} Route {$state}.</p>";
      } else {
        self::prepTickets();
        if (!empty($this->singleLocation)) {
          for ($i = 0; $i < count($this->singleLocation); $i++) {
            $ticket = self::createTicket($this->singleLocation[$i]);
            if ($ticket === false) {
              $temp = $this->error . "\n";
              $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
              if ($this->enableLogging !== false) self::writeLoop();
              return "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
            }
            $output .= $ticket->displaySingleTicket();
          }
        }
        if (!empty($this->multiLocation)) {
          foreach ($this->multiLocation as $group) {
            $temp = array();
            for ($i = 0; $i < count($group); $i++) {
              $ticket = self::createTicket($group[$i]);
              if ($ticket === false) {
                $temp = $this->error . "\n";
                $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
                if ($this->enableLogging !== false) self::writeLoop();
                return "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
              }
              $temp[] = $ticket;
            }
            $ticketPrimeData = [ 'multiTicket'=>$temp ];
            $ticketPrime = self::createTicket($ticketPrimeData);
            if ($ticketPrime === false) {
              $temp = $this->error . "\n";
              $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
              if ($this->enableLogging !== false) self::writeLoop();
              return "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
            }
            $output .= $ticketPrime->displayMultiTicket();
          }
        }
      }
      return $output;
    }

    public function transferredTickets()
    {
      $this->contractTicketSet = $this->singleLocation = [];
      $returnData = '';
      // Drivers without dispatch authorization need a single datalist for transferring tickets.
      if ($this->ulevel === "driver" && $this->CanDispatch === 0) {
        if ($this->transferList === null) {
          self::fetchDriversTransfer();
          if ($this->transferList === false) {
            $temp = $this->error . "\n";
            $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
            if ($this->enableLogging !== false) self::writeLoop();
            return false;
          }
        }
        if ($this->transferList !== 'empty' && $this->transferList !== null) {
          $returnData = '<datalist id="receivers">';
          foreach (json_decode($this->transferList, true) as $driver) {
            $driverName = ($driver['LastName'] == null) ? htmlentities($driver['FirstName']) . '; ' . $driver['DriverID'] : htmlentities($driver['FirstName']) . ' ' . htmlentities($driver['LastName']) . '; ' . $driver['DriverID'];
            $returnData .= ($driver['DriverID'] !== $_SESSION['DriverID']) ?
            "<option value=\"{$driverName}\">{$driverName}</option>" : '';
          }
          $returnData .= '</datalist>';
        }
      }
      $this->processTransfer = true;
      $transfersQueryData['endPoint'] = 'tickets';
      $transfersQueryData['method'] = 'GET';
      $transfersQueryData['queryParams']['filter'] = [];
      $transfersQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'bt', 'Value'=>'1,5'],
        ['Resource'=>'dTimeStamp', 'Filter'=>'is']
      ];
      $transfersQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>6],
        ['Resource'=>'d2TimeStamp', 'Filter'=>'is']
      ];
      $transfersQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'PendingReceiver', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'bt', 'Value'=>'1,5'],
        ['Resource'=>'dTimeStamp', 'Filter'=>'is']
      ];
      $transfersQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'PendingReceiver', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>6],
        ['Resource'=>'d2TimeStamp', 'Filter'=>'is']
      ];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>7],
        ['Resource'=>'dTimeStamp', 'Filter'=>'is'],
        ['Resource'=>'d2SigReq', 'Filter'=>'eq', 'Value'=>0]
      ];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>7],
        ['Resource'=>'d2TimeStamp', 'Filter'=>'is'],
        ['Resource'=>'d2SigReq', 'Filter'=>'eq', 'Value'=>1]
      ];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'PendingReceiver', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>7],
        ['Resource'=>'dTimeStamp', 'Filter'=>'is'],
        ['Resource'=>'d2SigReq', 'Filter'=>'eq', 'Value'=>0]
      ];
      $ticketQueryData['queryParams']['filter'][] = [
        ['Resource'=>'TransferState', 'Filter'=>'eq', 'Value'=>1],
        ['Resource'=>'PendingReceiver', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'Charge', 'Filter'=>'eq', 'Value'=>7],
        ['Resource'=>'d2TimeStamp', 'Filter'=>'is'],
        ['Resource'=>'d2SigReq', 'Filter'=>'eq', 'Value'=>1]
      ];
      if (!$transfersQuery = self::createQuery($transfersQueryData)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->error;
      }
      $this->activeTicketSet = self::callQuery($transfersQuery);
      if ($this->activeTicketSet === false) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->error;
      }
      if (empty($this->activeTicketSet)) {
        $returnData .= '<p class="center result">No Pending Transfers On File.</p>';
        return $returnData;
      }
      foreach ($this->activeTicketSet as $test) {
        $test["processTransfer"] = $this->processTransfer;
        if ($test["Contract"] === 1) {
          $this->contractTicketSet[] = $test;
        } else {
          $this->singleLocation[] = $test;
        }
      }
      if (empty($this->contractTicketSet) && empty($this->singleLocation)) {
        $returnData .= '<p class="center result">No Pending Transfers On File.</p>';
        return $returnData;
      }
      if (!empty($this->contractTicketSet)) {
        $this->activeTicketSet = $this->contractTicketSet;
        self::prepTickets();
      }
      if (!empty($this->singleLocation)) {
        for ($i = 0; $i < count($this->singleLocation); $i++) {
          $this->singleLocation[$i]['processTransfer'] = true;
          $ticket = self::createTicket($this->singleLocation[$i]);
          if ($ticket === false) {
            $temp = $this->error;
            $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
            if ($this->enableLogging !== false) self::writeLoop();
            $returnData .= "<p class=\"center result\"><span class=\"error\">Error</span>: {$this->error}</p>";
          } else {
            $returnData .= $ticket->displaySingleTicket();
          }
        }
      }
      if (!empty($this->multiLocation)) {
        foreach ($this->multiLocation as $group) {
          $temp = array();
          for ($i = 0; $i < count($group); $i++) {
            $ticket = self::createTicket($group[$i]);
            if ($ticket === false) {
              $temp = $this->error . "\n";
              $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
              if ($this->enableLogging !== false) self::writeLoop();
              $returnData .= "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
            }
            $temp[] = $ticket;
          }
          $ticketPrimeData = [ 'multiTicket'=>$temp ];
          $ticketPrimeData['processTransfer'] = true;
          $ticketPrime = self::createTicket($ticketPrimeData);
          if ($ticketPrime === false) {
            $temp = $this->error . "\n";
            $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
            if ($this->enableLogging !== false) self::writeLoop();
            $returnData .= "<p class=\"center result\"><span class=\"error\>Error</span>: {$this->error}</p>";
          }
          $returnData .= $ticketPrime->displayMultiTicket();
        }
      }
      return $returnData;
    }

    private function buildRoute()
    {
      // Check if driver has been seen today
      if ($this->LastSeen === null || $this->LastSeen !== $this->today) {
        // Pull list of runs dispatched to this driver
        self::fetchRunList();
        if ($this->runList === false) {
          $temp = $this->error . "\n";
          $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
          if ($this->enableLogging !== false) self::writeLoop();
          return false;
        }
        // Process the reschedule codes
        if (!empty($this->runList)) {
          self::processScheduleCodes();
          // Filter runs by schedule, check them against the schedule override, and add them to the daily ticket set
          self::filterRuns();
        }
        if (!empty($this->newTickets)) {
          if (!self::submitRouteTickets()) {
            $temp = $this->error . "\n";
            $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
            if ($this->enableLogging !== false) self::writeLoop();
            return false;
          }
        }
        // Set that driver has been seen today
        if (!self::setLastSeen()) {
          $temp = $this->error . "\n";
          $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
          if ($this->enableLogging !== false) self::writeLoop();
          return false;
        }
      }
      return true;
    }

    private function fetchDriversTransfer()
    {
      // Pull the data to make the datalists
      $driverQueryData['method'] = 'GET';
      $driverQueryData['endPoint'] = 'drivers';
      $driverQueryData['queryParams']['include'] = ['DriverID', 'FirstName', 'LastName'];
      $driverQueryData['queryParams']['filter'] = [ ['Resource'=>'Deleted', 'Filter'=>'neq', 'Value'=>1] ];
      if (!$driverQuery = self::createQuery($driverQueryData)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->transferList = false;
      }
      $tempDriver = self::callQuery($driverQuery);
      if ($tempDriver === false) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->transferList = false;
      }
      // Only proceed if a record is returned
      if (empty($tempDriver)) {
        return $this->transferList = 'empty';
      }
      return $this->transferList = json_encode($tempDriver);
    }

    private function fetchRunList()
    {
      $goodVals = [ 'Client', 'Department', 'Contact', 'Telephone', 'Address1', 'Address2', 'Country' ];
      $runVals = [ 'DryIce', 'diWeight', 'Notes', 'PriceOverride', 'TicketPrice', 'RoundTrip' ];
      $rescheduledQueryData['endPoint'] = 'schedule_override';
      $rescheduledQueryData['method'] = 'GET';
      $rescheduleFilter = [
        ['Resource'=>'Cancel', 'Filter'=>'eq', 'Value'=>5],
        ['Resource'=>'DriverID', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'StartDate', 'Filter'=>'le', 'Value'=>$this->today],
        ['Resource'=>'EndDate', 'Filter'=>'ge', 'Value'=>$this->today]
      ];
      $cancelFilter = [
        ['Resource'=>'Cancel', 'Filter'=>'le', 'Value'=>4],
        ['Resource'=>'StartDate', 'Filter'=>'le', 'Value'=>$this->today],
        ['Resource'=>'EndDate', 'Filter'=>'ge', 'Value'=>$this->today]
      ];
      $rescheduledQueryData['queryParams']['filter'] = [ $rescheduleFilter, $cancelFilter ];
      $rescheduledQueryData['queryParams']['join'] = [ 'contract_runs,contract_locations' ];
      $rescheduledQueryData['queryParams']['order'] = [ 'Cancel' ];
      if (!$rescheduledQuery = self::createQuery($rescheduledQueryData)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        $this->runList = false;
        return false;
      }
      $this->rescheduledRuns = self::callQuery($rescheduledQuery);
      if ($this->rescheduledRuns === false) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        $this->runList = false;
        return false;
      }
      for ($i = 0; $i < count($this->rescheduledRuns); $i++) {
        if ($this->cancelRoute === true) break;
        switch ($this->rescheduledRuns[$i]['Cancel']) {
          case 1: $this->cancelRoute = true;
            // no break
          case 2:
          case 3:
          case 4:
            $this->cancelations[] = $this->rescheduledRuns[$i]['RunNumber'];
            break;
          case 5:
            if (
              $this->cancelRoute === true ||
              in_array($this->rescheduledRuns[$i]['RunNumber'], $this->cancelations, true)
            ) break;
            $this->rescheduledRunsList[] = $this->rescheduledRuns[$i]['RunNumber'];
            foreach ($this->rescheduledRuns[$i]['run_id']['pickup_id'] as $key => $value) {
              if (in_array($key, $goodVals)) $this->rescheduledRuns[$i]["p{$key}"] = self::decode($value);
            }
            foreach ($this->rescheduledRuns[$i]['run_id']['dropoff_id'] as $key => $value) {
              if (in_array($key, $goodVals)) $this->rescheduledRuns[$i]["d{$key}"] = self::decode($value);
            }
            foreach ($this->rescheduledRuns[$i]['run_id'] as $key => $value) {
              if (in_array($key, $runVals)) $this->rescheduledRuns[$i][$key] = $value;
            }
            unset($this->rescheduledRuns[$i]['run_id']);
            $this->newTickets[] = $this->rescheduledRuns[$i];
            break;
        }
      }
      if ($this->cancelRoute === true) return false;
      $runListQueryData['endPoint'] = 'contract_runs';
      $runListQueryData['method'] = 'GET';
      $runListQueryData['queryParams']['filter'] = [
        ['Resource'=>'DispatchedTo', 'Filter'=>'eq', 'Value'=>$this->driverID],
        ['Resource'=>'RunNumber', 'Filter'=>'nin', 'Value'=>implode(',', array_merge($this->cancelations, $this->rescheduledRunsList))]
      ];
      $runListQueryData['queryParams']['join'] = [ 'contract_locations' ];
      if (!$runListQuery = self::createQuery($runListQueryData)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return $this->runList = false;
      }
      $temp = self::callQuery($runListQuery);
      for ($i = 0; $i < count($temp); $i++) {
        foreach ($temp[$i]['pickup_id'] as $key => $value) {
          if (in_array($key, $goodVals)) $temp[$i]["p{$key}"] = self::decode($value);
        }
        unset($temp[$i]['pickup_id']);
        foreach ($temp[$i]['dropoff_id'] as $key => $value) {
          if (in_array($key, $goodVals)) $temp[$i]["d{$key}"] = self::decode($value);
        }
        unset($temp[$i]['dropoff_id']);
        $this->runList = $temp;
      }
    }

    private function processScheduleCodes()
    {
      for ($i = 0; $i < count($this->runList); $i++) {
        // Process the schedule codes
        if (strpos($this->runList[$i]['Schedule'], ',')) {
          $this->runList[$i]['Schedule'] = explode(',', $this->runList[$i]['Schedule']);
          for ($x = 0; $x < count($this->runList[$i]['Schedule']); $x++) {
            $this->runList[$i]['Schedule'][$x] = self::scheduleFrequency($this->runList[$i]['Schedule'][$x]);
          }
        } else {
          $this->runList[$i]['Schedule'] = array(self::scheduleFrequency($this->runList[$i]['Schedule']));
        }
      }
    }

    private function scheduleFrequency($code)
    {
      $x = $y = $schedule = '';
      $test = explode(' ', $code);
      if (count($test) === 1) {
        switch(substr($code, 0, 1)) {
          case 'a':
            $x = 'Every';
            break;
          case 'b':
            $x = 'Every Other';
            break;
          case 'c':
            $x = 'Every First';
            break;
          case 'd':
            $x = 'Every Second';
            break;
          case 'e':
            $x = 'Every Third';
            break;
          case 'f':
            $x = 'Every Fourth';
            break;
          case 'g':
            $x = 'Every Last';
            break;
        }
        switch (substr($code, 1, 1)) {
          case '1':
            $y = 'Day';
            break;
          case '2':
            $y = 'Weekday';
            break;
          case '3':
            $y = 'Monday';
            break;
          case '4':
            $y = 'Tuesday';
            break;
          case '5':
            $y = 'Wednesday';
            break;
          case '6':
            $y = 'Thursday';
            break;
          case '7':
            $y = 'Friday';
            break;
          case '8':
            $y = 'Saturday';
            break;
          case '9':
            $y = 'Sunday';
            break;
        }
        $schedule = "{$x} {$y}";
      } else {
        if(count($test) === 3) {
          // If the literal schedule is 3 words long the first must be Every and can be eliminated
          array_shift($test);
        }
        switch($test[0]) {
          case 'Every':
            $x = 'a';
            break;
          case 'Other':
            $x = 'b';
            break;
          case 'First':
            $x = 'c';
            break;
          case '"econd':
            $x = 'd';
            break;
          case 'Third':
            $x = 'e';
            break;
          case 'Fourth':
            $x = 'f';
            break;
          case 'Last':
            $x = 'g';
            break;
        }
        switch ($test[1]) {
          case 'Day':
            $y = '1';
            break;
          case 'Weekday':
            $y = '2';
            break;
          case 'Monday':
            $y = '3';
            break;
          case 'Tuesday':
            $y = '4';
            break;
          case 'Wednesday':
            $y = '5';
            break;
          case 'Thursday':
            $y = '6';
            break;
          case 'Friday':
            $y = '7';
            break;
          case 'Saturday':
            $y = '8';
            break;
          case 'Sunday':
            $y = '9';
            break;
        }
        $schedule = "{$x} {$y}";
      }
      return $schedule;
    }

    private function filterRuns()
    {
      for($i = 0; $i < count($this->runList); $i++) {
        for ($x = 0; $x < count($this->runList[$i]['Schedule']); $x++) {
          // If the run has never been completed set LastCompleted to one day prior
          if ($this->runList[$i]['LastCompleted'] === $this->tTest) {
            $this->testDate = clone $this->dateObject;
            $this->testDate->modify('- 1 day')->format('Y-m-d');
          } else {
            $this->testDate = $this->runList[$i]['LastCompleted'];
          }
          if (self::compareSchedule($this->runList[$i]['RunNumber'], $this->runList[$i]['Schedule'][$x]) === true) {
            // Set a flag indicating that the ticket should be added to the new ticket set
            $this->add = true;
            // After the first ticket is added
            // set the flag to false if the new ticket set contains a ticket with the same run number
            if (!empty($this->newTickets)) {
              foreach ($this->newTickets as $test) {
                if ($this->runList[$i]['RunNumber'] === $test['RunNumber']) {
                  $this->add = false;
                }
              }
            }
            if ($this->add === true) {
              $this->newTickets[] = $this->runList[$i];
            }
          }
        }
      }
    }

    private function compareSchedule($runNumber, $scheduleFrequency)
    {
      if ($this->testDate === $this->today) {
        return false;
      }
      try {
        $this->testDateObject = new \dateTime($this->testDate, $this->timezone);
      } catch (Exception $e) {
        $this->error = 'Date Error Line ' . __line__ . ': ' . $e->getMessage();
        if ($this->enableLogging !== false) self::writeLoop();
        return false;
      }
      if ($this->error != null) return false;
      $test = explode(' ', $scheduleFrequency);
      if ($test[0] !== 'Every') {
        $this->error = 'Something is very wrong. This error should never occur. Line ' . __line__;
        if ($this->enableLogging !== false) self::writeLoop();
        return false;
      }
      if (count($test) === 2) {
        switch ($test[1]) {
          case 'Day': return true;
          case 'Weekday': return $this->dateObject->format('N') <= 5;
          default: return $test[1] === $this->dateObject->format('l');
        }
      } elseif (count($test) === 3) {
        if ($test[2] === 'Day' || $test[2] === 'Weekday' || $test[2] === $this->dateObject->format('l')) {
          return self::testFrequency($test[1], $test[2]);
        } else {
          return false;
        }
      }
    }

    private function testFrequency($test, $dayName)
    {
      switch ($test) {
        case 'Other':
          switch ($dayName) {
            case 'Day':
              return $this->testDateObject->format('j') % 2 === $this->dateObject->format('j') % 2;
              break;
            case 'Weekday':
              $diff = $this->dateObject->diff($this->testDateObject);
              //If it's been more than two days and today is not Monday
              if ($diff->d > 2 && $this->dateObject->format('N') > 1) {
                return $diff->d % 2 == 0;
              } else {
                return $this->testDateObject->modify('+ 2 weekdays')->format('Y-m-d') === $this->dateObject->format('Y-m-d');
              }
              break;
            default:
              /*Sun - Sat*/
              return $this->testDateObject->modify('+ 1 fortnight')->format('Y-m-d') === $this->dateObject->format('Y-m-d'); break;
          }
          break;
        case 'First':
          if ($dayName === 'Weekday') {
            return self::isFirstWeekday($this->dateObject);
          } else {
            return $this->dateObject->format('Y-m-d') === date('Y-m-d', strtotime("first {$dayName} of {$this->dateObject->format('F Y')}"));
          }
          break;
        case 'Second':
          if ($dayName === 'Weekday') {
            return self::isFirstWeekday($this->dateObject->modify('- 1 day'));
          } else {
            return $this->dateObject->format('Y-m-d') === date('Y-m-d', strtotime("second {$dayName} of
            {$this->dateObject->format('F Y')}"));
          }
          break;
        case 'Third':
          if ($dayName === 'Weekday') {
            return self::isFirstWeekday($this->dateObject->modify("- 2 day"));
          } else {
            return $this->dateObject->format('Y-m-d') === date('Y-m-d', strtotime("third {$dayName} of {$this->dateObject->format('F Y')}"));
          }
          break;
        case 'Fourth':
          if ($dayName === 'Weekday') {
            return self::isFirstWeekday($this->dateObject->modify('- 3 day'));
          } else {
            return $this->dateObject->format('Y-m-d') === date('Y-m-d', strtotime("fourth {$dayName} of {$this->dateObject->format('F Y')}"));
          }
          break;
        case 'Last':
          if ($dayName === 'Weekday') {
            switch ($this->dateObject->format('t') - $this->dateObject->format('j')) {
              case 0: return $this->dateObject->format('N') <= 5;
              case 1:
              case 2: return $this->dateObject->format('N') == 5;
              default: return false;
            }
          } else {
            return $this->dateObject->format('Y-m-d') === date('Y-m-d', strtotime("last {$dayName} of {$this->dateObject->format('F Y')}"));
          }
          break;
        default: return false;
      }
    }
    /** http://stackoverflow.com/questions/33446530/testing-for-the-first-weekday-of-the-month-in-php **/
    private function isFirstWeekday($dateObject)
    {
      switch($dateObject->format('j')) {
        case 1: return $dateObject->format('N') <= 5;
        case 2:
        case 3: return $dateObject->format('N') == 1;
        default: return false;
      }
    }

    private function submitRouteTickets()
    {
      $data['multiTicket'] = [];
      foreach ($this->newTickets as $newTicket) {
        $newTicket['ReadyDate'] = "{$this->dateObject->format('Y-m-d')} {$newTicket['pTime']}";
        $micro_date = microtime();
        $date_array = explode(' ',$micro_date);
        $newTicket['TicketNumber'] = $newTicket['RunNumber'] . $this->dateObject->format('m') . '00';
        $newTicket['Contract'] = 1;
        $newTicket['DispatchTimeStamp'] =
          $newTicket['ReceivedDate'] =
          $this->dateObject->format('Y-m-d H:i:s');
        $newTicket['DispatchMicroTime'] = substr($date_array[0], 1, 7);
        $newTicket['DispatchedTo'] = $this->driverID;
        $newTicket['DispatchedBy'] = '1.1';
        $newTicket['Charge'] = ($newTicket['RoundTrip'] === 1) ? 6 : 5;
        $newTicket['TicketBase'] = $newTicket['TicketPrice'];

        $data['multiTicket'][] = $newTicket;
      }
      if (!$ticketPrime = self::createTicket($data)) {
        $temp = $this->error . "\n";
        $this->error = __function__ . ' Line ' . __line__ . ': ' . $temp;
        if ($this->enableLogging !== false) self::writeLoop();
        return false;
      }
      if ($ticketPrime->processRouteTicket() === false) {
        $this->error .= "\n" . $ticketPrime->getError();
        if ($this->enableLogging !== false) self::writeLoop();
        return false;
      }
      return true;
    }

    private function sortTickets($ticket)
    {
      if (
        array_key_exists($ticket['locationTest'], $this->multiLocation) &&
        self::recursive_array_search($ticket['TicketNumber'], $this->multiLocation) === false
      ) {
        $this->multiLocation[$ticket['locationTest']][] = $ticket;
      } else {
        if (empty($this->ticketSet)) {
          $this->ticketSet[] = $ticket;
        } else {
          $match = 0;
          for ($i = 0; $i < count($this->ticketSet); $i++) {
            if ($this->ticketSet[$i]['locationTest'] === $ticket['locationTest']) {
              $this->multiLocation[$ticket['locationTest']][] = $this->ticketSet[$i];
              $this->multiLocation[$ticket['locationTest']][] = $ticket;
              $match++;
            }
          }
          if ($match === 0) $this->ticketSet[] = $ticket;
        }
      }
    }

    private function prepTickets()
    {
      // Set new keys 1) using client name, department, address1, and schedule time for grouping tickets,
      // 2) indicating what step the ticket is on to ease processing.
      foreach ($this->activeTicketSet as $ticket) {
        $readyObj = new \dateTime($ticket['ReadyDate']);
        if ($ticket['pTimeStamp'] === $this->tTest) {
          $ticket['locationTest'] =
            "{$ticket['pClient']}{$ticket['pDepartment']}{$ticket['pAddress1']}{$readyObj->format('Y-m-dH:ia')}";
          $ticket['step'] = 'pickedUp';
          self::sortTickets($ticket);
        } elseif ($ticket['pTimeStamp'] !== $this->tTest && $ticket['dTimeStamp'] === $this->tTest) {
          $dTimeArray = explode(':', $ticket['dTime']);
          $readyObj->setTime($dTimeArray[0], $dTimeArray[1], $dTimeArray[2]);
          if ($ticket['pTime'] > $ticket['dTime']) {
            $readyObj->modify('+ 1 day');
          }
          $ticket['locationTest'] =
            "{$ticket['dClient']}{$ticket['dDepartment']}{$ticket['dAddress1']}{$readyObj->format('Y-m-dH:ia')}";
          $ticket['step'] = 'delivered';
          self::sortTickets($ticket);
        } elseif ($ticket['pTimeStamp'] !== $this->tTest && $ticket['dTimeStamp'] !== $this->tTest) {
          // Non round trip tickets with dTimeStamp !== $tTest will not have been returned from the database. No need to test the charge code.
          $d2TimeArray = explode(':', $ticket['d2Time']);
          $readyObj->setTime($d2TimeArray[0], $d2TimeArray[1], $d2TimeArray[2]);
          if ($ticket['dTime'] > $ticket['d2Time']) {
            $readyObj->modify('+ 1 day');
          }
          $ticket['locationTest'] =
            "{$ticket['pClient']}{$ticket['pDepartment']}{$ticket['pAddress1']}{$readyObj->format('Y-m-dH:ia')}";
          $ticket['step'] = 'returned';
          self::sortTickets($ticket);
        }
      }
      foreach ($this->ticketSet as $ticket) {
        if (!array_key_exists($ticket['locationTest'], $this->multiLocation)) {
          $this->singleLocation[] = $ticket;
        }
      }
    }
  }
