

function ButtonCard(props){
    return(
        <div className="w-80 m-auto p-2 shadow-lg rounded-sm shadow flex justify-between">
            <div>
                <img className="invert" src={props.icon} alt={props.alt} />
            </div>
            <div>
                <h2 className="text-lg">{props.texto}</h2>
                <p className="text-gray-400 text-sm">({props.status})</p>
            </div>
            <div className="">
                <img src="/icons/seta-icon.svg" alt="Seta" />
            </div>

            
        </div>
    )
}

export default ButtonCard;