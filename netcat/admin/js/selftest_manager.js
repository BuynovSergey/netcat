class SelftestManager {
    currentStep = 1;
    totalTestsCount = 0;
    progress = 0;
    currentSiteId = 0;
    testsData = {};
    currentTestData = {};
    problemsCount = {};
    _initialSiteCounters = {}
    hasTests = true;
    isRunning = false;
    currentTestName = null;

    constructor(initialTestName, initialSiteCounters, totalTestsCount, initialSiteId) {
        this.currentTestName = initialTestName;
        this.totalTestsCount = totalTestsCount;
        this.currentSiteId = initialSiteId;
        this._initialSiteCounters = { ...initialSiteCounters, shared: 0 };
        Object.assign(this.problemsCount, this._initialSiteCounters);
    }

    init() {
        this.isRunning = true;
        this.reset();
    }

    reset() {
        this.currentStep = 1;
        this.testsData = {};
        this.currentTestData = {};
        this.hasTests = true;
        this.currentTestName = null;
        Object.assign(this.problemsCount, this._initialSiteCounters);
    }

    shutdown() {
        this.isRunning = false;
    }

    processResult(result) {
        this.testsData[this.currentStep] = result.results;
        this.currentTestData = result.results;
        this.currentStep = result.next_step;
        this.hasTests = !result.test_ends
        this.currentTestName = this.isTestShared() ? this.currentTestData.label : this.currentTestData[this.currentSiteId].label;
        this._calculateProblemsCount();
        this._calculateProgress();
    }

    _calculateProblemsCount() {
        if (this.isTestShared()) {
            if (this._isStatusCritical(this.currentTestData.status)) {
                this.problemsCount.shared++;
            }
        } else {
            for (let siteId in this.currentTestData) {
                if (this._isStatusCritical(this.currentTestData[siteId].status)) {
                    this.problemsCount[siteId]++;
                }
            }
        }
    }

    _calculateProgress() {
        this.progress = Math.floor(this.getCompletedTests() * (100 / this.getTotalTestsCount()));
    }

    isTestShared() {
        return "shared" in this.currentTestData;
    }

    _isStatusCritical(status) {
        return status === "critical";
    }

    getProblemsCount(allSites = false) {
        const problemsCountClone = Object.assign({}, this.problemsCount);
        const hasSites = Object.values(problemsCountClone).length > 1;

        if (allSites) {
            delete problemsCountClone.shared;

            let result = 0;

            for (const key in problemsCountClone) {
                result += problemsCountClone[key];
            }

            return result + this.problemsCount.shared;
        }

        return hasSites ? this.problemsCount[this.currentSiteId] + this.problemsCount.shared : this.problemsCount.shared;
    }

    getTotalTestsCount() {
        return this.totalTestsCount;
    }

    getCompletedTests() {
        return Object.values(this.testsData).length;
    }

    skipTest() {
        this.currentStep++;
    }

}
