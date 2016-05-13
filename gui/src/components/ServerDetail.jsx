import React from 'react';
import Classname from 'classname';

export default class ServerDetail extends React.Component {

    render() {
        const {hostInfo, buildInfo, init, index} = this.props;

        return (
            <div className={Classname('col-md-6 col-xs-12', {clearfix: index % 2 === 0})}>
                <div className="panel panel-primary" id={`panel-${index}`} >
                    <div className="panel-heading" role="tab">
                        <h3 className="panel-title">
                            <a
                                role="button"
                                data-toggle="collapse"
                                href={`#panel-collapse-${index}`}
                                aria-controls={`panel-collapse-${index}`}
                                aria-expanded={index === 0}>
                                {this.props.url}
                            </a>
                        </h3>
                    </div>
                    <div id={`panel-collapse-${index}`} className="panel-collapse collapse in" aria-expanded={index === 0}>
                        <div className="panel-body">

                            <h4>Host info</h4>
                            {this.renderHostInfo(hostInfo.length && hostInfo[0].data)}

                            <h4>Build info</h4>
                            {this.renderBuildInfo(buildInfo.length && buildInfo[0].data)}

                            {this.renderDatabases(init.length && init[0].listDBs.databases)}
                        </div>
                    </div>
                </div>
            </div>
        )
    }

    renderHostInfo(data) {
        if (! data) {
            return null;
        }
        return (
            <table className="table table-bordered">
                <tbody>
                    <tr>
                        <td>versionSignature</td>
                        <td>{data.extra.versionSignature}</td>
                    </tr>
                    <tr>
                        <td>hostname</td>
                        <td>{data.system.hostname}</td>
                    </tr>
                    <tr>
                        <td>memSizeMB</td>
                        <td>{data.system.memSizeMB}</td>
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
    
    renderDatabases(data) {
        return (
            <div>
                <a className="btn btn-primary" role="button" data-toggle="collapse" href={`#databases${this.props.index}`} aria-expanded="false" aria-controls={`#databases${this.props.index}`}>
                    Show/hide databases
                </a>

                <div className="collapse" id={`databases${this.props.index}`}>
                    {data.sort((a, b) => a.name > b.name).map((db) => {
                        return (
                            <dl className="dl-horizontal">
                                <dt>name</dt>
                                <dd>{db.name}</dd>
                                <dt>empty</dt>
                                <dd>{db.empty ? 'yes' : 'no'}</dd>
                            </dl>
                        )
                    })}
                </div>
            </div>
        )
    }

}