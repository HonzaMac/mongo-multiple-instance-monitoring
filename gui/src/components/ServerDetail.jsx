import React from 'react';
import Classname from 'classname';
import Humanize from 'humanize';

import JsonDetail from './JsonDetail.jsx';

export default class ServerDetail extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
          details: false
        };

        this.lastUpdate = (new Date()).toLocaleTimeString();
    }

    /**
     * For huge data load this component must detect last updated items and by them do or not do rendering
     *
     * @param next
     * @param nextState
     * @returns {boolean|*}
     */
    shouldComponentUpdate(next, nextState) {
        const {props: prev} = this;
        const shouldUpdate = ! prev.hostInfo && typeof next.hostInfo !== 'undefined' ||
                ( typeof prev.hostInfo !== 'undefined' && prev.hostInfo.lastUpdate < next.hostInfo.lastUpdate) ||
                ! prev.buildInfo && typeof next.buildInfo !== 'undefined' ||
                ( typeof prev.buildInfo !== 'undefined' && prev.buildInfo.lastUpdate < next.buildInfo.lastUpdate) ||
                ! prev.init && typeof next.init !== 'undefined' ||
                ( typeof prev.init !== 'undefined' && prev.init.lastUpdate < next.init.lastUpdate) ||
                ! prev.log && typeof next.log !== 'undefined' ||
                ( typeof prev.log !== 'undefined' && prev.log.lastUpdate < next.log.lastUpdate) ||
                ! prev.dbStats && typeof next.dbStats !== 'undefined' ||
                ( typeof prev.dbStats !== 'undefined' && ServerDetail.compareDbStatsFreshness(prev.dbStats, next.dbStats));
        if (shouldUpdate) {
            this.lastUpdate = (new Date()).toLocaleTimeString();
        }

        return shouldUpdate || this.state.details !== nextState.details;
    }

    static compareDbStatsFreshness(prev, next) {

        /**
         * Check for newly updated DB stats
         */
        for (let key of Object.keys(prev)) {
            /**
             * If dbStats lastUpdate timestamp is different then update view
             */
            if (typeof next[key] !== 'undefined' && prev[key].lastUpdate < next[key].lastUpdate) {
                return true;
            }
        }

        return false;
    }

    render() {
        const {hostInfo, buildInfo, init, index, log, dbStats} = this.props;

        return (
            <div className={Classname('col-md-4 col-xs-12', {clearfix: index % 3 === 0})}>
                <div className="panel panel-primary" id={`panel-${index}`}>
                    <div className="panel-heading" role="tab">
                        <h3 className="panel-title">
                            <a
                                onClick={() => this.setState({details: ! this.state.details})}
                                role="button"
                                href="javascript:;">
                                {this.props.url}
                            </a>
                            <small className="pull-right"><strong>Last update:</strong> {this.lastUpdate}</small>
                        </h3>
                    </div>
                    <div className="panel-body">
                        <h4>Host info <JsonDetail title="Host info" code={JSON.stringify(hostInfo && hostInfo.data)} /></h4>
                        {this.renderHostInfo(hostInfo && hostInfo.data)}

                        {! this.state.details ? <div>
                            <h4>Databases <JsonDetail title="Databases" code={JSON.stringify(init && init.listDBs.databases)} /></h4>
                            <p>{init && init.listDBs.databases ? init.listDBs.databases.map((db) => db.name).join(', ') : null}</p>
                        </div> : <div>
                            <h4>Build info <JsonDetail title="Build info" code={JSON.stringify(buildInfo && buildInfo.data)} /></h4>
                            {this.renderBuildInfo(buildInfo && buildInfo.data)}

                            <h4>Databases <JsonDetail title="Databases" code={JSON.stringify(init && init.listDBs.databases)} /></h4>
                            {this.renderDatabases(init && init.listDBs.databases, dbStats)}

                            <h4>Logs <JsonDetail title="Databases" code={JSON.stringify(log && log.data)} /></h4>
                            {this.renderLogMessages(log && log.data)}
                        </div>}
                    </div>
                </div>
            </div>
        )
    }

    renderHostInfo(data) {
        if (!data) {
            return null;
        }
        return (
            <table className="table table-bordered">
                <tbody>
                <tr>
                    <td>versionSignature</td>
                    {data.os ? <td>{data.os.name} {data.os.version} {data.os.type}</td> : <td></td>}
                </tr>
                <tr>
                    <td>hostname</td>
                    {data.system ? <td>{data.system.hostname}</td> : <td></td>}
                </tr>
                <tr>
                    <td>memSizeMB</td>
                    {data.system ? <td>{data.system.memSizeMB}</td> : <td></td>}
                </tr>
                </tbody>
            </table>
        )
    }

    renderBuildInfo(data) {
        return (
            <table className="table table-bordered">
                <tbody>
                <tr>
                    <td>version</td>
                    <td>{data.version}</td>
                </tr>
                </tbody>
            </table>
        )
    }

    renderDatabases(data, stats) {

        const renderStats = (stats) => {
          return (
              <p>
                  Collections <span className="badge pull-right">{stats.collections}</span><br />
                  Indexes <span className="badge pull-right">{stats.indexes}</span><br />
                  Index sizes <span className="badge pull-right">{stats.indexSize >= 0 ? Humanize.filesize(stats.indexSize) : null}</span><br />
                  File size <span className="badge pull-right">{stats.fileSize >= 0 ? Humanize.filesize(stats.fileSize) : null}</span>
              </p>
          )
        };

        return (
            <ul className="list-group">
                {data.sort((a, b) => a.name > b.name).map((db, dbIndex) => {

                    return (
                        <li key={`detail-db-${dbIndex}`} className="list-group-item">
                            <strong>{db.name}</strong>
                            {stats && stats[db.name] ? renderStats(stats[db.name].data) : null}
                        </li>
                    )
                })}
            </ul>
        )
    }

    renderLogMessages(data) {
        if (typeof data !== 'undefined' && Object.keys(data).length) {
            return (
                <div style={{color: '#0c0', backgroundColor: '#222', maxHeight: '400px', overflow: 'scroll'}}>
                    {Object.keys(data).reverse().map((key, rowKey) => {
                        return <p key={`log-row-${rowKey}`}>{data[key]}</p>
                    })}
                </div>
            )
        } else {
            return <p>Waiting for logs...</p>
        }
    }
}